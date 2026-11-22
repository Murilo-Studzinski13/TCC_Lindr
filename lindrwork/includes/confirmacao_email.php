<?php
declare(strict_types = 1);

/*
 * Verificação em duas etapas por e-mail (como a conta do Google):
 *  - em um dispositivo NOVO, depois da senha, a pessoa digita um código de 6 dígitos enviado ao e-mail;
 *  - se marcar "confiar neste dispositivo", ele fica cadastrado por 30 dias e não pede o código de novo;
 *  - o mesmo código confirma que o e-mail existe e é dela (o administrador é dispensado dessa confirmação,
 *    mas também usa a verificação em duas etapas).
 */

/** A verificação em duas etapas é obrigatória? (LINDR_2FA_OBRIGATORIO=0 desliga, só para testes locais) */
function duas_etapas_obrigatorio(): bool {
    return getenv('LINDR_2FA_OBRIGATORIO') !== '0';
}

const DISPOSITIVO_DIAS = 30;

/** "Chrome em Windows", a partir do navegador (só para a pessoa reconhecer o aparelho na lista). */
function dispositivo_nome(): string {
    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $os = 'Dispositivo';
    foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone|iPad' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $re => $nome) {
        if (preg_match('/' . $re . '/i', $ua)) {
            $os = $nome;
            break;
        }
    }
    $br = 'Navegador';
    foreach (['Edg' => 'Edge', 'OPR|Opera' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'] as $re => $nome) {
        if (preg_match('/' . $re . '/i', $ua)) {
            $br = $nome;
            break;
        }
    }
    return $br . ' em ' . $os;
}

function dispositivo_cookie(int $userId): string {
    return 'lindr_dev_' . $userId;
}

/** Este navegador já foi marcado como confiável por esta pessoa (e ainda não venceu)? */
function dispositivo_confiavel(PDO $pdo, int $userId): bool {
    $t = (string)($_COOKIE[dispositivo_cookie($userId)] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $t)) return false;
    $s = $pdo->prepare('SELECT id FROM dispositivos_confiaveis WHERE usuario_id=? AND token_hash=? AND expira_em > NOW() LIMIT 1');
    $s->execute([$userId, hash('sha256', $t)]);
    $id = $s->fetchColumn();
    if (!$id) return false;
    $pdo->prepare('UPDATE dispositivos_confiaveis SET ultimo_uso=NOW() WHERE id=?')->execute([$id]);
    return true;
}

/** Id do dispositivo atual na tabela (para marcar "este aparelho" na lista), ou null. */
function dispositivo_atual_id(PDO $pdo, int $userId): ?int {
    $t = (string)($_COOKIE[dispositivo_cookie($userId)] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $t)) return null;
    $s = $pdo->prepare('SELECT id FROM dispositivos_confiaveis WHERE usuario_id=? AND token_hash=? LIMIT 1');
    $s->execute([$userId, hash('sha256', $t)]);
    $id = $s->fetchColumn();
    return $id ? (int)$id : null;
}

/** Cadastra este navegador como confiável por 30 dias (cookie + só o hash no banco). */
function dispositivo_confiar(PDO $pdo, int $userId): void {
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('DELETE FROM dispositivos_confiaveis WHERE usuario_id=? AND expira_em < NOW()')->execute([$userId]);
    // no máximo 10 aparelhos: o mais antigo sai
    $pdo->prepare('DELETE FROM dispositivos_confiaveis WHERE usuario_id=? AND id NOT IN (SELECT id FROM (SELECT id FROM dispositivos_confiaveis WHERE usuario_id=? ORDER BY id DESC LIMIT 9) x)')
        ->execute([$userId, $userId]);
    $pdo->prepare('INSERT INTO dispositivos_confiaveis(usuario_id,token_hash,nome,expira_em) VALUES(?,?,?,DATE_ADD(NOW(), INTERVAL ' . DISPOSITIVO_DIAS . ' DAY))')
        ->execute([$userId, hash('sha256', $token), mb_substr(dispositivo_nome(), 0, 150)]);
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie(dispositivo_cookie($userId), $token, ['expires' => time() + DISPOSITIVO_DIAS * 86400, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
}

/** Remove um aparelho (ou todos, quando $deviceId é null). Use ao trocar a senha. */
function dispositivos_revogar(PDO $pdo, int $userId, ?int $deviceId = null): void {
    if ($deviceId === null) {
        $pdo->prepare('DELETE FROM dispositivos_confiaveis WHERE usuario_id=?')->execute([$userId]);
    } else {
        $pdo->prepare('DELETE FROM dispositivos_confiaveis WHERE usuario_id=? AND id=?')->execute([$userId, $deviceId]);
    }
}

/** Esta conta ainda precisa confirmar o e-mail? */
function email_precisa_confirmar(array $u): bool {
    if (($u['nivel_acesso'] ?? 'usuario') === 'admin') return false;
    return empty($u['email_confirmado_em']);
}

/**
 * Confere se o domínio do e-mail existe de verdade (pega "gmial.com", "hotmal.com"...).
 * Se o servidor estiver sem internet/DNS, não bloqueia ninguém.
 * Para desligar: LINDR_VERIFICAR_DNS=0.
 */
function email_dominio_existe(string $email): bool {
    if (getenv('LINDR_VERIFICAR_DNS') === '0') return true;
    $at = strrpos($email, '@');
    if ($at === false) return false;
    $domain = strtolower(substr($email, $at + 1));
    if ($domain === '' || !function_exists('checkdnsrr')) return true;
    if (checkdnsrr($domain . '.', 'MX') || checkdnsrr($domain . '.', 'A') || checkdnsrr($domain . '.', 'AAAA')) return true;
    // o domínio não respondeu: se um domínio conhecido também não responde, é o DNS do servidor que está fora
    $dnsFunciona = checkdnsrr('google.com.', 'A') || checkdnsrr('google.com.', 'MX');
    return !$dnsFunciona;
}

/** "maria.silva@gmail.com" -> "ma***@gmail.com" */
function email_mascarado(string $email): string {
    $at = strrpos($email, '@');
    if ($at === false) return $email;
    $user = substr($email, 0, $at);
    return mb_substr($user, 0, min(2, max(1, mb_strlen($user) - 1))) . '***' . substr($email, $at);
}

/**
 * Gera e envia um código novo (os anteriores deixam de valer).
 * Limites: 1 envio por minuto (quando $respeitarIntervalo) e no máximo 5 por hora.
 * Em localhost sem SMTP o código aparece na tela (modo de demonstração).
 * Retorna [ok, mensagem_de_erro|null].
 */
function email_enviar_codigo(PDO $pdo, array $u, bool $respeitarIntervalo = true): array {
    $id = (int)$u['id'];
    $c = $pdo->prepare("SELECT COUNT(*) AS n, MAX(criado_em) > DATE_SUB(NOW(), INTERVAL 60 SECOND) AS recente FROM codigos_verificacao WHERE usuario_id=? AND tipo='email' AND criado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $c->execute([$id]);
    $r = $c->fetch();
    if ((int)$r['n'] >= 5) return [false, 'Você pediu códigos demais. Aguarde cerca de 1 hora e tente de novo.'];
    if ($respeitarIntervalo && !empty($r['recente'])) return [false, 'Aguarde 1 minuto antes de pedir outro código.'];

    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE codigos_verificacao SET usado=1 WHERE usuario_id=? AND tipo='email' AND usado=0")->execute([$id]);
    // só o hash do código fica no banco
    $pdo->prepare("INSERT INTO codigos_verificacao(usuario_id,codigo,tipo,expira_em) VALUES(?,?,'email',DATE_ADD(NOW(), INTERVAL 15 MINUTE))")
        ->execute([$id, hash('sha256', $code)]);

    $cfg = require __DIR__ . '/../config/mail.php';
    $smtpOk = $cfg['username'] && $cfg['password'] && $cfg['from_email'];
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    // contas de demonstração (e-mails @lindr.demo / @lindr.com não existem): em localhost o código aparece na tela
    $demoMail = (bool)preg_match('/@(lindr\.demo|lindr\.com)$/i', (string)$u['email']);
    if ($local && (!$smtpOk || $demoMail)) {
        $_SESSION['dev_codigo_email'] = $code;
        return [true, null];
    }
    try {
        require_once __DIR__ . '/mailer.php';
        enviar_codigo_verificacao((string)$u['email'], (string)($u['nome_social'] ?: $u['nome']), $code);
    } catch (Throwable $e) {
        error_log('Lindr confirmação de e-mail: ' . $e->getMessage());
        return [false, 'Não foi possível enviar o e-mail agora. Tente de novo em instantes.'];
    }
    return [true, null];
}

/**
 * Confere o código digitado. Retorna 'ok', 'errado', 'expirado' ou 'muitas' (5 erros: peça outro código).
 * Quando está certo, marca o e-mail como confirmado.
 */
function email_verificar_codigo(PDO $pdo, int $userId, string $digitado): string {
    $digitado = preg_replace('/\D/', '', $digitado) ?? '';
    $s = $pdo->prepare("SELECT id,codigo,tentativas FROM codigos_verificacao WHERE usuario_id=? AND tipo='email' AND usado=0 AND expira_em > NOW() ORDER BY id DESC LIMIT 1");
    $s->execute([$userId]);
    $row = $s->fetch();
    if (!$row) return 'expirado';
    $usar = $pdo->prepare('UPDATE codigos_verificacao SET usado=1 WHERE id=?');
    if ((int)$row['tentativas'] >= 5) {
        $usar->execute([$row['id']]);
        return 'muitas';
    }
    if (strlen($digitado) === 6 && hash_equals((string)$row['codigo'], hash('sha256', $digitado))) {
        $usar->execute([$row['id']]);
        $pdo->prepare('UPDATE usuarios SET email_confirmado_em=NOW() WHERE id=?')->execute([$userId]);
        return 'ok';
    }
    $pdo->prepare('UPDATE codigos_verificacao SET tentativas=tentativas+1 WHERE id=?')->execute([$row['id']]);
    if ((int)$row['tentativas'] + 1 >= 5) {
        $usar->execute([$row['id']]);
        return 'muitas';
    }
    return 'errado';
}
