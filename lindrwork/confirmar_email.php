<?php
require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/confirmacao_email.php';
if (user()) redirect('index.php');

// só chega aqui quem acabou de criar a conta ou de digitar a senha certa em um aparelho novo (sessão ainda NÃO autenticada)
$p = $_SESSION['email_pendente'] ?? null;
if (!$p || ($p['ate'] ?? 0) < time()) {
    unset($_SESSION['email_pendente'], $_SESSION['dev_codigo_email']);
    flash('Entre na sua conta ou crie uma para continuar.', 'error');
    redirect('login.php');
}
$s = $pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');
$s->execute([(int)$p['id']]);
$u = $s->fetch();
if (!$u) {
    unset($_SESSION['email_pendente'], $_SESSION['dev_codigo_email']);
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'resend') {
        [$ok, $err] = email_enviar_codigo($pdo, $u, true);
        flash($ok ? 'Enviamos um novo código. Confira também o spam.' : $err, $ok ? 'info' : 'error');
        redirect('confirmar_email.php');
    }
    $primeiraVez = email_precisa_confirmar($u);
    $res = email_verificar_codigo($pdo, (int)$u['id'], (string)($_POST['code'] ?? ''));
    if ($res === 'ok') {
        unset($_SESSION['email_pendente'], $_SESSION['dev_codigo_email']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$u['id'];
        // "Confiar neste dispositivo": não pede o código de novo neste navegador por 30 dias
        if (!empty($_POST['confiar'])) dispositivo_confiar($pdo, (int)$u['id']);
        flash($primeiraVez ? 'E-mail confirmado! Bem-vinde ao Lindr.' : 'Bem-vinde de volta!');
        redirect(($u['nivel_acesso'] ?? 'usuario') === 'admin' ? 'admin/index.php' : 'index.php');
    }
    $msgs = [
        'errado' => 'Código incorreto. Confira o e-mail e tente de novo.',
        'expirado' => 'O código expirou. Peça um novo abaixo.',
        'muitas' => 'Muitas tentativas erradas. Peça um novo código abaixo.',
    ];
    flash($msgs[$res] ?? 'Não foi possível confirmar.', 'error');
    redirect('confirmar_email.php');
}

$dev = $_SESSION['dev_codigo_email'] ?? null;
$page = 'login';
$title = 'Verificação em duas etapas — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <div class="auth">
        <div class="l"><small class="ey lt">SEGURANÇA</small>
            <h1>Verificação em <em>duas etapas.</em></h1>
            <p>Sua senha está certa. Falta só confirmar que é você mesmo.</p>
            <div class="ck"><span class="sq2"><?= icon('check', 15) ?></span>Abra o e-mail e copie o código de 6 dígitos</div>
            <div class="ck"><span class="sq2"><?= icon('check', 15) ?></span>O código vale por 15 minutos</div>
            <div class="ck"><span class="sq2"><?= icon('check', 15) ?></span>Não chegou? Veja a caixa de spam</div>
        </div>
        <div class="r">
            <div class="ac">
                <a class="bk" href="login.php">← Voltar</a>
                <h2>Digite o código</h2>
                <p class="mut">Enviado para <strong><?= e(email_mascarado($u['email'])) ?></strong></p>
                <?php if ($dev): ?>
                    <div class="card" style="padding:10px;border-color:#d9a400">
                        Modo de teste (localhost sem SMTP configurado): seu código é <strong style="letter-spacing:4px"><?= e($dev) ?></strong>
                    </div>
                <?php endif; ?>
                <form method="post"><?= csrf_field() ?>
                    <label for="code">Código de 6 dígitos</label>
                    <input id="code" name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" autofocus placeholder="000000" style="font-size:22px;letter-spacing:6px;text-align:center">
                    <label class="checkbox-line" style="margin-top:14px"><input type="checkbox" name="confiar" value="1"> <span>Confiar neste dispositivo por <?= DISPOSITIVO_DIAS ?> dias (não pedir o código de novo aqui)</span></label>
                    <button class="btn wide" type="submit" style="margin-top:14px">Verificar <?= icon('arr', 18) ?></button>
                </form>
                <form method="post" style="margin-top:10px"><?= csrf_field() ?><input type="hidden" name="action" value="resend">
                    <button class="btn ghost wide" type="submit">Enviar um novo código</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
