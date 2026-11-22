<?php
// 2ª etapa do login: a senha já foi conferida, mas a sessão ainda NÃO está autenticada.
// - Conta com 2FA ativo: pede o código do aplicativo (ou um código de backup).
// - Conta sem 2FA (e 2FA obrigatório): configura o autenticador AQUI, antes de entrar no site.
require __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
require_once __DIR__.'/includes/totp.php';

if (user()) redirect('index.php');
if (isset($_GET['cancelar'])) { unset($_SESSION['2fa_pendente']); redirect('login.php'); }

$p = $_SESSION['2fa_pendente'] ?? null;
if (!$p || (int)($p['ate'] ?? 0) < time()) {
    unset($_SESSION['2fa_pendente']);
    flash('A verificação expirou. Entre novamente.', 'error');
    redirect('login.php');
}
$s = $pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');
$s->execute([(int)$p['id']]);
$u = $s->fetch();
if (!$u) { unset($_SESSION['2fa_pendente']); redirect('login.php'); }

$configurar = empty($u['totp_ativo']);
if ($configurar && !tfa_obrigatorio()) { unset($_SESSION['2fa_pendente']); redirect('login.php'); }

/* ================= CONFIGURAR (primeiro acesso, ainda sem autenticador) ================= */
if ($configurar) {
    if (empty($p['segredo'])) { $_SESSION['2fa_pendente']['segredo'] = totp_novo_segredo(); }
    $segredo = (string)$_SESSION['2fa_pendente']['segredo'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $step = totp_verify($segredo, preg_replace('/\s+/', '', (string)($_POST['code'] ?? '')), 0);
        if ($step === null) {
            $_SESSION['2fa_pendente']['falhas'] = (int)($_SESSION['2fa_pendente']['falhas'] ?? 0) + 1;
            if ($_SESSION['2fa_pendente']['falhas'] >= TFA_MAX_FALHAS) {
                unset($_SESSION['2fa_pendente']);
                flash('Muitas tentativas incorretas. Entre novamente.', 'error');
                redirect('login.php');
            }
            flash('Código incorreto. Confira o aplicativo e tente de novo.', 'error');
            redirect('verificacao_2fa.php');
        }
        try { $enc = tfa_encrypt($segredo); }
        catch (Throwable $e) { flash($e->getMessage(), 'error'); redirect('verificacao_2fa.php'); }

        $pdo->prepare('UPDATE usuarios SET totp_secret=?, totp_ativo=1, totp_ultimo_passo=?, totp_falhas=0, totp_bloqueado_ate=NULL WHERE id=?')
            ->execute([$enc, $step, $u['id']]);
        $_SESSION['2fa_codigos'] = tfa_gerar_backup($pdo, (int)$u['id']);
        notify_user((int)$u['id'], 'sistema', 'Verificação em duas etapas ativada', 'A partir de agora o login pede um código do seu aplicativo autenticador.', 'dois_fatores.php');

        unset($_SESSION['2fa_pendente']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        flash('Verificação em duas etapas ativada! Guarde seus códigos de backup.');
        // a página abaixo mostra os códigos de backup uma única vez
        redirect('dois_fatores.php');
    }

    $uri = totp_uri($segredo, (string)$u['email']);
    $segredoFmt = trim(chunk_split($segredo, 4, ' '));
    $page = 'login'; $title = 'Ativar verificação em duas etapas — Lindr';
    require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><div class="auth"><div class="l"><small class="ey lt">SEGURANÇA</small><h1>Proteja sua <em>conta.</em></h1><p>No Lindr, todo acesso exige uma verificação em duas etapas. Leva um minuto e só precisa ser configurada uma vez.</p><?php foreach(['Instale um aplicativo autenticador (Google Authenticator, Authy...)','Escaneie o QR Code ao lado','Digite o código de 6 dígitos para confirmar'] as $x): ?><div class="ck"><span class="sq2"><?=icon('check',15)?></span><?=$x?></div><?php endforeach; ?></div><div class="r"><form class="ac" method="post" autocomplete="off"><?=csrf_field()?><a class="bk" href="verificacao_2fa.php?cancelar=1">← Cancelar</a><h2>Ativar verificação</h2><p class="mut">Conta: <b><?=e($u['email'])?></b></p>
<div class="qr-box" id="qr" data-uri="<?=e($uri)?>"><span class="mut" style="padding:12px;text-align:center">Ative o JavaScript para ver o QR Code ou use a chave abaixo.</span></div>
<p class="mut" style="margin:10px 0 4px">Não consegue escanear? Digite esta chave no aplicativo (tipo: baseada em tempo):</p><span class="secret-key"><?=e($segredoFmt)?></span>
<label for="code" style="margin-top:16px">Código de 6 dígitos</label><input id="code" name="code" class="code-input" required autofocus inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="000000"><button class="btn wide" type="submit">Ativar e entrar <?=icon('arr',18)?></button></form></div></div></div>
<script src="js/qrcode.js"></script><script src="js/dois_fatores.js"></script>
<?php require __DIR__.'/includes/footer.php'; exit; }

/* ================= VERIFICAR (conta com 2FA ativo) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $r = tfa_check($pdo, $u, (string)($_POST['code'] ?? ''));

    if ($r === 'ok' || $r === 'ok_backup') {
        unset($_SESSION['2fa_pendente']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        if ($r === 'ok_backup') {
            $left = tfa_backup_restantes($pdo, (int)$u['id']);
            notify_user((int)$u['id'], 'sistema', 'Código de backup utilizado', 'Você entrou com um código de backup. Restam '.$left.'. Se não foi você, troque sua senha.', 'dois_fatores.php');
            flash('Você entrou com um código de backup. Restam '.$left.'.');
        } else {
            flash('Bem-vinde de volta!');
        }
        redirect(($u['nivel_acesso'] ?? 'usuario') === 'admin' ? 'admin/index.php' : 'index.php');
    }

    $min = tfa_minutos_bloqueio($pdo, (int)$u['id']);
    flash($min > 0 ? 'Muitas tentativas incorretas. Tente novamente em '.$min.' minuto(s).' : 'Código incorreto. Confira o aplicativo e tente de novo.', 'error');
    redirect('verificacao_2fa.php');
}

$page = 'login'; $title = 'Verificação em duas etapas — Lindr';
require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><div class="auth"><div class="l"><small class="ey lt">SEGURANÇA</small><h1>Verificação em <em>duas etapas.</em></h1><p>Sua senha está certa. Falta só confirmar que é você mesmo.</p><?php foreach(['Abra o aplicativo autenticador no celular','Digite o código de 6 dígitos que aparece','Os códigos mudam a cada 30 segundos'] as $x): ?><div class="ck"><span class="sq2"><?=icon('check',15)?></span><?=$x?></div><?php endforeach; ?></div><div class="r"><form class="ac" method="post" autocomplete="off"><?=csrf_field()?><a class="bk" href="verificacao_2fa.php?cancelar=1">← Cancelar</a><h2>Digite o código</h2><p class="mut">Conta: <b><?=e($u['email'])?></b></p><label for="code">Código do aplicativo</label><input id="code" name="code" class="code-input" required autofocus autocomplete="one-time-code" autocapitalize="off" spellcheck="false" maxlength="14" placeholder="000000"><button class="btn wide" type="submit">Verificar <?=icon('arr',18)?></button><p class="mut" style="margin-top:18px">Sem acesso ao aplicativo? Digite um dos seus <b>códigos de backup</b> (formato <code>xxxxx-xxxxx</code>) no mesmo campo. Cada código só vale uma vez.</p></form></div></div></div><?php require __DIR__.'/includes/footer.php'; ?>
