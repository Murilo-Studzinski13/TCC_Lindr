<?php
require __DIR__.'/config/database.php';
require_once __DIR__.'/includes/totp.php';
if(user()) redirect('index.php');
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $email=strtolower(trim($_POST['email']??'')); $pass=(string)($_POST['password']??'');
    $ip=substr((string)($_SERVER['REMOTE_ADDR']??''),0,45);

    // Limite de tentativas: 8 erros por e-mail ou 30 por IP em 15 minutos (contra adivinhação de senhas).
    $throttle=true;
    try{
        $c=$pdo->prepare('SELECT (SELECT COUNT(*) FROM tentativas_login WHERE email=? AND criado_em > DATE_SUB(NOW(), INTERVAL 15 MINUTE)) AS por_email, (SELECT COUNT(*) FROM tentativas_login WHERE ip=? AND criado_em > DATE_SUB(NOW(), INTERVAL 15 MINUTE)) AS por_ip');
        $c->execute([$email,$ip]); $n=$c->fetch();
        if((int)$n['por_email']>=8 || (int)$n['por_ip']>=30){ flash('Muitas tentativas de login. Aguarde alguns minutos e tente de novo.','error'); redirect('login.php'); }
    }catch(PDOException $e){ $throttle=false; error_log('Lindr: tabela tentativas_login ausente (rode evolucao_migration.sql)'); }

    $s=$pdo->prepare('SELECT * FROM usuarios WHERE email=? AND ativo=1'); $s->execute([$email]); $u=$s->fetch();
    // Mesmo sem conta, verifica um hash falso: o tempo de resposta não revela se o e-mail existe.
    $ok=password_verify($pass,$u['senha_hash'] ?? '$2y$10$YuFzd4R70nud30otnITIWu3RE83odJYdVNlCQkDislx/9b0NLnJ9C') && $u;
    if(!$ok){
        if($throttle){ $pdo->prepare('INSERT INTO tentativas_login(email,ip) VALUES(?,?)')->execute([mb_substr($email,0,255),$ip]); }
        flash('E-mail ou senha incorretos','error'); redirect('login.php');
    }
    if($throttle){ $pdo->prepare('DELETE FROM tentativas_login WHERE email=?')->execute([$email]); }
    session_regenerate_id(true);
    if(!empty($u['totp_ativo']) || tfa_obrigatorio()){
        // senha correta, mas falta a 2ª etapa: a sessão ainda NÃO está autenticada.
        // Quem ainda não configurou o autenticador faz isso agora, antes de entrar no site.
        $_SESSION['2fa_pendente']=['id'=>(int)$u['id'],'ate'=>time()+900];
        redirect('verificacao_2fa.php');
    }
    $_SESSION['user_id']=(int)$u['id'];
    flash('Bem-vinde de volta!');
    if (($u['nivel_acesso'] ?? 'usuario') === 'admin') {
        redirect('admin/index.php');
    }
    redirect('index.php');
}
$page='login';$title='Entrar — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><div class="auth"><div class="l"><small class="ey lt">BEM-VINDO DE VOLTA</small><h1>Suas conexões <em>esperam por você.</em></h1><p>Volte para o seu espaço e continue de onde parou.</p><?php foreach(['Converse com pessoas que combinam com você','Descubra novas comunidades e interesses','Tenha uma experiência simples e acolhedora'] as $x): ?><div class="ck"><span class="sq2"><?=icon('check',15)?></span><?=$x?></div><?php endforeach; ?></div><div class="r"><form class="ac" method="post"><?=csrf_field()?><a class="bk" href="index.php">← Voltar</a><h2>Entrar</h2><p class="mut">Acesse sua conta do Lindr.</p><label>E-mail</label><input name="email" type="email" required placeholder="seu@email.com"><div class="password-label"><label for="login-password">Senha</label><a class="forgot-link" href="senha_esqueci.php">Esqueci minha senha</a></div><input id="login-password" name="password" type="password" required placeholder="Sua senha" autocomplete="current-password"><button class="btn wide" type="submit">Entrar <?=icon('arr',18)?></button><div class="or"><span>ou</span></div><a class="btn ghost wide signup-button" href="cadastro.php">Criar nova conta</a><p class="mut signup-note">Ainda não tem uma conta? <a class="lk2" href="cadastro.php">Criar agora</a></p></form></div></div></div><?php require __DIR__.'/includes/footer.php'; ?>
