<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
require_once __DIR__.'/includes/totp.php';
$me = require_login();
$ativo = !empty($me['totp_ativo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if (!$ativo && $action === 'ativar') {
        $setup = $_SESSION['2fa_setup'] ?? null;
        if (!$setup || (int)$setup['ate'] < time()) {
            unset($_SESSION['2fa_setup']);
            flash('A configuração expirou. Comece de novo.', 'error');
            redirect('dois_fatores.php');
        }
        $step = totp_verify($setup['secret'], preg_replace('/\s+/', '', (string)($_POST['code'] ?? '')), 0);
        if ($step === null) { flash('Código incorreto. Confira o aplicativo e tente de novo.', 'error'); redirect('dois_fatores.php'); }
        try { $enc = tfa_encrypt($setup['secret']); }
        catch (Throwable $e) { flash($e->getMessage(), 'error'); redirect('dois_fatores.php'); }

        $pdo->prepare('UPDATE usuarios SET totp_secret=?, totp_ativo=1, totp_ultimo_passo=?, totp_falhas=0, totp_bloqueado_ate=NULL WHERE id=?')
            ->execute([$enc, $step, $me['id']]);
        $_SESSION['2fa_codigos'] = tfa_gerar_backup($pdo, (int)$me['id']);
        unset($_SESSION['2fa_setup']);
        notify_user((int)$me['id'], 'sistema', 'Verificação em duas etapas ativada', 'A partir de agora o login pede um código do seu aplicativo autenticador.', 'dois_fatores.php');
        flash('Verificação em duas etapas ativada!');
        redirect('dois_fatores.php');
    }

    if ($ativo && $action === 'desativar' && tfa_obrigatorio()) {
        flash('A verificação em duas etapas é obrigatória no Lindr e não pode ser desativada.', 'error');
        redirect('dois_fatores.php');
    }
    if ($ativo && in_array($action, ['desativar', 'novos_codigos'], true)) {
        // ação sensível: exige a senha E um código do aplicativo (ou de backup)
        if (!password_verify((string)($_POST['senha'] ?? ''), $me['senha_hash'])) { flash('Senha incorreta.', 'error'); redirect('dois_fatores.php'); }
        $r = tfa_check($pdo, $me, (string)($_POST['code'] ?? ''));
        if ($r !== 'ok' && $r !== 'ok_backup') {
            $min = tfa_minutos_bloqueio($pdo, (int)$me['id']);
            flash($min > 0 ? 'Muitas tentativas incorretas. Tente novamente em '.$min.' minuto(s).' : 'Código incorreto.', 'error');
            redirect('dois_fatores.php');
        }
        if ($action === 'desativar') {
            $pdo->prepare('UPDATE usuarios SET totp_secret=NULL, totp_ativo=0, totp_ultimo_passo=NULL, totp_falhas=0, totp_bloqueado_ate=NULL WHERE id=?')->execute([$me['id']]);
            $pdo->prepare('DELETE FROM backup_codes_2fa WHERE usuario_id=?')->execute([$me['id']]);
            notify_user((int)$me['id'], 'sistema', 'Verificação em duas etapas desativada', 'Se não foi você, troque sua senha imediatamente.', 'configuracoes.php');
            flash('Verificação em duas etapas desativada.');
        } else {
            $_SESSION['2fa_codigos'] = tfa_gerar_backup($pdo, (int)$me['id']);
            flash('Novos códigos de backup gerados. Os anteriores deixaram de valer.');
        }
        redirect('dois_fatores.php');
    }
    redirect('dois_fatores.php');
}

// códigos recém-gerados: aparecem uma única vez
$novosCodigos = $_SESSION['2fa_codigos'] ?? null;
unset($_SESSION['2fa_codigos']);

$uri = $segredoFmt = null; $erroConfig = null;
if (!$ativo) {
    $setup = $_SESSION['2fa_setup'] ?? null;
    if (!$setup || (int)$setup['ate'] < time()) {
        $setup = ['secret' => totp_novo_segredo(), 'ate' => time() + 900];
        $_SESSION['2fa_setup'] = $setup;
    }
    $uri = totp_uri($setup['secret'], $me['email']);
    $segredoFmt = trim(chunk_split($setup['secret'], 4, ' '));
}
$restantes = $ativo ? tfa_backup_restantes($pdo, (int)$me['id']) : 0;

$page = 'dois_fatores'; $title = 'Verificação em duas etapas — Lindr';
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap" style="max-width:720px">
<a class="bk" href="configuracoes.php#duas-etapas">← Configurações</a>
<h1 style="margin-top:8px">Verificação em duas etapas</h1>
<p class="mut">Além da senha, o login passa a pedir um código temporário gerado no seu celular. Mesmo que alguém descubra sua senha, não consegue entrar sem o seu aparelho.</p>

<?php if ($novosCodigos): ?>
<section class="card" style="border-color:var(--pri)">
    <h2>Guarde seus códigos de backup</h2>
    <p>Se você perder o celular, cada código abaixo permite <b>um</b> acesso. <b>Eles só aparecem agora</b>: copie, baixe ou imprima e guarde em um lugar seguro.</p>
    <ul class="backup-codes" id="backup-list"><?php foreach ($novosCodigos as $c): ?><li><?=e($c)?></li><?php endforeach; ?></ul>
    <div class="actions"><button type="button" class="btn secondary" id="copy-codes">Copiar</button><button type="button" class="btn secondary" id="download-codes">Baixar .txt</button><button type="button" class="btn secondary" id="print-codes">Imprimir</button></div>
</section>
<?php endif; ?>

<?php if (!$ativo): ?>
<section class="card">
    <h2>Ativar</h2>
    <ol class="steps-2fa">
        <li>Instale um aplicativo autenticador (Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden...).</li>
        <li>No aplicativo, escolha <b>adicionar conta</b> e <b>escaneie o QR Code</b> abaixo.</li>
        <li>Digite aqui o código de 6 dígitos que o aplicativo mostrar.</li>
    </ol>
    <div class="qr-box" id="qr" data-uri="<?=e($uri)?>"><span class="mut" style="padding:12px;text-align:center">Ative o JavaScript para ver o QR Code ou use a chave abaixo.</span></div>
    <p class="mut" style="margin:10px 0 4px">Não consegue escanear? Digite esta chave no aplicativo (tipo: baseada em tempo):</p>
    <span class="secret-key"><?=e($segredoFmt)?></span>
    <form method="post" style="margin-top:20px"><?=csrf_field()?><input type="hidden" name="action" value="ativar">
        <label for="code">Código de 6 dígitos</label>
        <input id="code" name="code" class="code-input" required inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="000000" style="max-width:240px">
        <div class="actions"><button class="btn" type="submit">Ativar verificação em duas etapas</button></div>
    </form>
</section>
<?php else: ?>
<section class="card">
    <h2>Status: <span class="pill">Ativada</span></h2>
    <p>Seu login está protegido por um código do aplicativo autenticador. Códigos de backup disponíveis: <b><?=$restantes?></b><?=$restantes <= 2 ? ' — <span style="color:#e5484d">poucos! Gere novos códigos.</span>' : ''?></p>
</section>
<details class="card">
    <summary style="cursor:pointer;font-weight:700">Gerar novos códigos de backup</summary>
    <p class="mut">Os códigos antigos deixam de valer. Confirme com sua senha e um código do aplicativo.</p>
    <form method="post"><?=csrf_field()?><input type="hidden" name="action" value="novos_codigos">
        <label>Senha</label><input type="password" name="senha" required autocomplete="current-password">
        <label>Código do aplicativo</label><input name="code" class="code-input" required autocomplete="one-time-code" maxlength="14" placeholder="000000" style="max-width:240px">
        <div class="actions"><button class="btn secondary" type="submit">Gerar novos códigos</button></div>
    </form>
</details>
<?php if (!tfa_obrigatorio()): ?>
<details class="card">
    <summary style="cursor:pointer;font-weight:700;color:#e5484d">Desativar a verificação em duas etapas</summary>
    <p class="mut">Sua conta voltará a usar só a senha, o que é menos seguro. Confirme com sua senha e um código.</p>
    <form method="post" onsubmit="return confirm('Desativar a verificação em duas etapas?')"><?=csrf_field()?><input type="hidden" name="action" value="desativar">
        <label>Senha</label><input type="password" name="senha" required autocomplete="current-password">
        <label>Código do aplicativo (ou de backup)</label><input name="code" class="code-input" required autocomplete="one-time-code" maxlength="14" placeholder="000000" style="max-width:240px">
        <div class="actions"><button class="btn danger" type="submit">Desativar</button></div>
    </form>
</details>
<?php else: ?>
<p class="mut">A verificação em duas etapas é obrigatória no Lindr e não pode ser desativada.</p>
<?php endif; ?>
<?php endif; ?>
</main></div>
<script src="js/qrcode.js"></script>
<?php require __DIR__.'/includes/footer.php'; ?>
