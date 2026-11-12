<?php
/**
 * Verificação em duas etapas (2FA) por aplicativo autenticador.
 * Implementa TOTP (RFC 6238): SHA-1, 6 dígitos, janela de 30 s — compatível com
 * Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden etc.
 * Não usa bibliotecas externas.
 */

const TFA_ISSUER        = 'Lindr';
const TFA_MAX_FALHAS    = 5;    // erros seguidos antes de bloquear
const TFA_BLOQUEIO_MIN  = 15;   // minutos de bloqueio
const TFA_BACKUP_QTD    = 8;    // códigos de backup gerados

/** A verificação em duas etapas é exigida de TODOS no login. Para desligar (só em testes): LINDR_2FA_OBRIGATORIO=0 */
function tfa_obrigatorio(): bool {
    return getenv('LINDR_2FA_OBRIGATORIO') !== '0';
}

/* ---------- Base32 (RFC 4648), alfabeto usado pelos autenticadores ---------- */
function base32_encode(string $bin): string {
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 5) as $chunk) $out .= $alpha[bindec(str_pad($chunk, 5, '0'))];
    return $out;
}
function base32_decode(string $s): string {
    $alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $s));
    if ($s === '') return '';
    $bits = '';
    foreach (str_split($s) as $c) $bits .= str_pad(decbin(strpos($alpha, $c)), 5, '0', STR_PAD_LEFT);
    $out = '';
    foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
    return $out;
}

/* ---------- Proteção do segredo no banco (AES-256-GCM) ---------- */
/** Chave: variável LINDR_APP_KEY ou, se não existir, o arquivo storage/app.key (criado automaticamente). */
function app_key(): string {
    $env = getenv('LINDR_APP_KEY');
    if ($env) return hash('sha256', $env, true);
    $file = __DIR__ . '/../storage/app.key';
    if (!is_file($file)) {
        $dir = dirname($file);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        if (@file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível criar storage/app.key. Dê permissão de escrita à pasta storage/ ou defina LINDR_APP_KEY.');
        }
        @chmod($file, 0600);
    }
    return hash('sha256', trim((string)file_get_contents($file)), true);
}
function tfa_encrypt(string $plain): string {
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($plain, 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($ct === false) throw new RuntimeException('Falha ao proteger o segredo (extensão openssl).');
    return 'v1:' . base64_encode($iv . $tag . $ct);
}
function tfa_decrypt(string $stored): ?string {
    if (strpos($stored, 'v1:') !== 0) return null;
    $raw = base64_decode(substr($stored, 3), true);
    if ($raw === false || strlen($raw) < 29) return null;
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', app_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    return $plain === false ? null : $plain;
}

/* ---------- TOTP ---------- */
function totp_novo_segredo(): string {
    return base32_encode(random_bytes(20)); // 160 bits, 32 caracteres
}
function totp_code(string $secretBase32, int $step): string {
    $key = base32_decode($secretBase32);
    $h = hash_hmac('sha1', pack('N2', 0, $step), $key, true);
    $o = ord($h[19]) & 0x0F;
    $v = ((ord($h[$o]) & 0x7F) << 24) | ((ord($h[$o + 1]) & 0xFF) << 16) | ((ord($h[$o + 2]) & 0xFF) << 8) | (ord($h[$o + 3]) & 0xFF);
    return str_pad((string)($v % 1000000), 6, '0', STR_PAD_LEFT);
}
/** Aceita o passo atual e ±1 (relógios levemente fora). Nunca aceita passo <= $lastStep (impede reaproveitar o mesmo código). */
function totp_verify(string $secret, string $code, int $lastStep = 0): ?int {
    if (!preg_match('/^\d{6}$/', $code)) return null;
    $now = intdiv(time(), 30);
    for ($i = -1; $i <= 1; $i++) {
        $step = $now + $i;
        if ($step <= $lastStep) continue;
        if (hash_equals(totp_code($secret, $step), $code)) return $step;
    }
    return null;
}
function totp_uri(string $secret, string $email): string {
    return 'otpauth://totp/' . rawurlencode(TFA_ISSUER) . ':' . rawurlencode($email)
         . '?secret=' . $secret . '&issuer=' . rawurlencode(TFA_ISSUER) . '&algorithm=SHA1&digits=6&period=30';
}

/* ---------- Códigos de backup (uso único) ---------- */
/** Gera novos códigos (apaga os antigos). Só o hash vai ao banco; os códigos em si são exibidos uma única vez. */
function tfa_gerar_backup(PDO $pdo, int $uid): array {
    $pdo->prepare('DELETE FROM backup_codes_2fa WHERE usuario_id=?')->execute([$uid]);
    $ins = $pdo->prepare('INSERT INTO backup_codes_2fa(usuario_id,codigo_hash) VALUES(?,?)');
    $codes = [];
    for ($i = 0; $i < TFA_BACKUP_QTD; $i++) {
        $c = strtolower(substr(base32_encode(random_bytes(7)), 0, 10));
        $ins->execute([$uid, hash('sha256', $c)]);
        $codes[] = substr($c, 0, 5) . '-' . substr($c, 5);
    }
    return $codes;
}
function tfa_backup_restantes(PDO $pdo, int $uid): int {
    $s = $pdo->prepare('SELECT COUNT(*) FROM backup_codes_2fa WHERE usuario_id=? AND usado_em IS NULL');
    $s->execute([$uid]);
    return (int)$s->fetchColumn();
}

/* ---------- Verificação com limite de tentativas ---------- */
/** Minutos que faltam para o bloqueio acabar (0 = sem bloqueio). */
function tfa_minutos_bloqueio(PDO $pdo, int $uid): int {
    $s = $pdo->prepare('SELECT IF(totp_bloqueado_ate IS NOT NULL AND totp_bloqueado_ate > NOW(), CEIL(TIMESTAMPDIFF(SECOND, NOW(), totp_bloqueado_ate) / 60), 0) FROM usuarios WHERE id=?');
    $s->execute([$uid]);
    return (int)$s->fetchColumn();
}
/**
 * Confere o código digitado (6 dígitos do aplicativo OU código de backup).
 * @return string 'ok' | 'ok_backup' | 'invalido' | 'bloqueado'
 */
function tfa_check(PDO $pdo, array $u, string $input): string {
    $uid = (int)$u['id'];
    $st = $pdo->prepare('SELECT totp_secret, totp_ultimo_passo FROM usuarios WHERE id=? AND totp_ativo=1');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row) return 'invalido';
    if (tfa_minutos_bloqueio($pdo, $uid) > 0) return 'bloqueado';

    $code = strtolower(preg_replace('/[\s-]+/', '', $input));
    $result = 'invalido';

    if (preg_match('/^\d{6}$/', $code)) {
        $secret = tfa_decrypt((string)$row['totp_secret']);
        $step = $secret !== null ? totp_verify($secret, $code, (int)$row['totp_ultimo_passo']) : null;
        if ($step !== null) {
            // UPDATE condicional: se duas requisições usarem o mesmo código ao mesmo tempo, só uma passa.
            $up = $pdo->prepare('UPDATE usuarios SET totp_ultimo_passo=? WHERE id=? AND (totp_ultimo_passo IS NULL OR totp_ultimo_passo < ?)');
            $up->execute([$step, $uid, $step]);
            if ($up->rowCount() === 1) $result = 'ok';
        }
    } elseif (preg_match('/^[a-z2-7]{10}$/', $code)) {
        $up = $pdo->prepare('UPDATE backup_codes_2fa SET usado_em=NOW() WHERE usuario_id=? AND codigo_hash=? AND usado_em IS NULL');
        $up->execute([$uid, hash('sha256', $code)]);
        if ($up->rowCount() === 1) $result = 'ok_backup';
    }

    if ($result === 'invalido') {
        $pdo->prepare('UPDATE usuarios SET totp_falhas = totp_falhas + 1 WHERE id=?')->execute([$uid]);
        $pdo->prepare('UPDATE usuarios SET totp_bloqueado_ate = DATE_ADD(NOW(), INTERVAL ' . TFA_BLOQUEIO_MIN . ' MINUTE), totp_falhas = 0 WHERE id=? AND totp_falhas >= ' . TFA_MAX_FALHAS)->execute([$uid]);
    } else {
        $pdo->prepare('UPDATE usuarios SET totp_falhas = 0, totp_bloqueado_ate = NULL WHERE id=?')->execute([$uid]);
    }
    return $result;
}
