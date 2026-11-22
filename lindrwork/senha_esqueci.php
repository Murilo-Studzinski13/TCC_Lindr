<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $s = $pdo->prepare('SELECT id,nome,nome_social,email FROM usuarios WHERE email=? AND ativo=1 LIMIT 1');
    $s->execute([$email]);
    $account = $s->fetch();
    if ($account) {
        // no máximo 3 pedidos por hora por conta (impede encher a caixa de e-mail de alguém)
        $c = $pdo->prepare('SELECT COUNT(*) FROM tokens_redefinicao_senha WHERE usuario_id=? AND criado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $c->execute([$account['id']]);
        if ((int)$c->fetchColumn() < 3) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $pdo->prepare('UPDATE tokens_redefinicao_senha SET usado=1 WHERE usuario_id=? AND usado=0')
                ->execute([$account['id']]);
            $pdo->prepare('INSERT INTO tokens_redefinicao_senha(usuario_id,token_hash,expira_em) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))')
                ->execute([$account['id'], $hash]);
            $url = app_url() . '/senha_redefinir.php?token=' . urlencode($token);
            $cfg = require __DIR__ . '/config/mail.php';
            $smtpOk = $cfg['username'] && $cfg['password'] && $cfg['from_email'];
            $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
            if (!$smtpOk && $local) {
                // Ambiente de desenvolvimento sem SMTP: mostra o link na tela para dar para testar/demonstrar.
                $_SESSION['dev_reset_link'] = $url;
            } else {
                try {
                    require_once __DIR__ . '/includes/mailer.php';
                    enviar_link_redefinicao($account['email'], $account['nome_social']?:$account['nome'], $url);
                } catch (Throwable $e) {
                    error_log('Lindr password reset mail: ' . $e->getMessage());
                }
=======
require __DIR__.'/config/database.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $email=mb_strtolower(trim($_POST['email']??''));
    $s=$pdo->prepare('SELECT id,nome,nome_social,email FROM usuarios WHERE email=? AND ativo=1 LIMIT 1');$s->execute([$email]);$account=$s->fetch();
    if($account){
        // no máximo 3 pedidos por hora por conta (impede encher a caixa de e-mail de alguém)
        $c=$pdo->prepare('SELECT COUNT(*) FROM tokens_redefinicao_senha WHERE usuario_id=? AND criado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)');$c->execute([$account['id']]);
        if((int)$c->fetchColumn()<3){
            $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);
            $pdo->prepare('UPDATE tokens_redefinicao_senha SET usado=1 WHERE usuario_id=? AND usado=0')->execute([$account['id']]);
            $pdo->prepare('INSERT INTO tokens_redefinicao_senha(usuario_id,token_hash,expira_em) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE))')->execute([$account['id'],$hash]);
            $url=app_url().'/senha_redefinir.php?token='.urlencode($token);
            $cfg=require __DIR__.'/config/mail.php';
            $smtpOk=$cfg['username']&&$cfg['password']&&$cfg['from_email'];
            $local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
            if(!$smtpOk && $local){
                // Ambiente de desenvolvimento sem SMTP: mostra o link na tela para dar para testar/demonstrar.
                $_SESSION['dev_reset_link']=$url;
            }else{
                try{require_once __DIR__.'/includes/mailer.php';enviar_link_redefinicao($account['email'],$account['nome_social']?:$account['nome'],$url);}
                catch(Throwable $e){error_log('Lindr password reset mail: '.$e->getMessage());}
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
            }
        }
    }
    flash('Se o e-mail estiver cadastrado, enviaremos instruções de redefinição. Confira também o spam.');
    redirect('senha_esqueci.php');
}
<<<<<<< HEAD
$devLink = $_SESSION['dev_reset_link'] ?? null;
unset($_SESSION['dev_reset_link']);
$page = 'login';
$title = 'Recuperar senha — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap" style="max-width:520px">
        <div class="card">
            <h1>Recuperar senha</h1>
            <p class="mut">Informe o e-mail da sua conta. Se ele estiver cadastrado e o SMTP estiver configurado, você receberá um link seguro.</p>
            <form method="post"><?= csrf_field() ?>
                <label>E-mail</label>
                <input type="email" name="email" required autocomplete="email">
                <div class="actions"><button class="btn">Enviar instruções</button><a class="btn secondary" href="login.php">Voltar ao login</a></div>
            </form>
            <?php if ($devLink): ?>
                <div class="notice" style="margin-top:16px"><b>Modo local (SMTP não configurado):</b> como o e-mail não pode ser enviado, use este link para redefinir a senha:<br><a href="<?=e($devLink)?>"><?= e($devLink) ?></a></div>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
$devLink=$_SESSION['dev_reset_link']??null;unset($_SESSION['dev_reset_link']);
$page='login';$title='Recuperar senha — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php';?>
<div class="pg"><main class="wrap" style="max-width:520px"><div class="card"><h1>Recuperar senha</h1><p class="mut">Informe o e-mail da sua conta. Se ele estiver cadastrado e o SMTP estiver configurado, você receberá um link seguro.</p><form method="post"><?=csrf_field()?> <label>E-mail</label><input type="email" name="email" required autocomplete="email"><div class="actions"><button class="btn">Enviar instruções</button><a class="btn secondary" href="login.php">Voltar ao login</a></div></form><?php if($devLink): ?><div class="notice" style="margin-top:16px"><b>Modo local (SMTP não configurado):</b> como o e-mail não pode ser enviado, use este link para redefinir a senha:<br><a href="<?=e($devLink)?>"><?=e($devLink)?></a></div><?php endif; ?></div></main></div><?php require __DIR__.'/includes/footer.php';?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
