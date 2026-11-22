<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/locais.php';
require_once __DIR__ . '/includes/confirmacao_email.php';
if (user()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $birth = trim($_POST['birth_date'] ?? '');
    $bd = DateTime::createFromFormat('!Y-m-d', $birth);
    $birthOk = $bd && $bd->format('Y-m-d') === $birth && $bd <= new DateTime('today');
    // estado e cidade são escolhidos da lista (não digitados)
    [$uf, $cidade, $locErr] = location_from_post('state', 'city', true);
    if (!$name || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) $err = 'Informe nome e e-mail válidos.';
    elseif (!email_dominio_existe($email)) $err = 'Não encontramos o provedor deste e-mail. Confira se digitou certo (ex.: gmail.com).';
    elseif (!$birthOk) $err = 'Informe sua data de nascimento.';
    elseif (age($birth) < 18) $err = 'O Lindr é exclusivo para maiores de 18 anos.';
    elseif ($locErr) $err = $locErr;
    elseif ($e2 = password_error($pass)) $err = $e2;
    elseif ($pass !== $confirm) $err = 'As senhas não coincidem.';
    elseif (empty($_POST['aceite'])) $err = 'Aceite os Termos de uso e a Política de privacidade para continuar.';
    else {
        $s = $pdo->prepare('SELECT * FROM usuarios WHERE email=?');
        $s->execute([$email]);
        $existing = $s->fetch();
        $uid = 0;
        if ($existing && (!email_precisa_confirmar($existing) || (int)$existing['ativo'] !== 1)) {
            $err = 'Este e-mail já está cadastrado.';
        } elseif ($existing) {
            // cadastro anterior com este e-mail que nunca foi confirmado (ex.: erro de digitação):
            // refazemos o cadastro, mas só entra quem confirmar o código enviado ao e-mail.
            $pdo->prepare('UPDATE usuarios SET nome=?, senha_hash=?, data_nascimento=?, cidade=?, estado=? WHERE id=?')
                ->execute([$name, password_hash($pass, PASSWORD_DEFAULT), $birth, $cidade, $uf, (int)$existing['id']]);
            $uid = (int)$existing['id'];
        } else {
            // verificado=0: o selo só deve aparecer depois de uma verificação real (e-mail/administração).
            try {
                $s = $pdo->prepare('INSERT INTO usuarios(nome,email,senha_hash,data_nascimento,cidade,estado,verificado) VALUES(?,?,?,?,?,?,0)');
                $s->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $birth, $cidade, $uf]);
                $uid = (int)$pdo->lastInsertId();
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') throw $e; // 23000 = e-mail duplicado (dois cadastros ao mesmo tempo)
                $err = 'Este e-mail já está cadastrado.';
            }
        }
        if (!isset($err) && $uid) {
            // a conta só entra no site depois de confirmar o e-mail (código enviado agora)
            [$sent, $sendErr] = email_enviar_codigo($pdo, ['id' => $uid, 'email' => $email, 'nome' => $name, 'nome_social' => null], false);
            session_regenerate_id(true);
            $_SESSION['email_pendente'] = ['id' => $uid, 'ate' => time() + 1800];
            if ($sent) flash('Conta criada! Enviamos um código de 6 dígitos para o seu e-mail.');
            else flash(($sendErr ?: 'Não foi possível enviar o e-mail.') . ' Você pode pedir um novo código na próxima tela.', 'error');
            redirect('confirmar_email.php');
        }
    }
}
$page = 'cadastro';
$title = 'Criar conta — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <div class="auth">
        <div class="l"><small class="ey lt">FAÇA PARTE</small>
            <h1>Seu espaço.<br><em>Sua comunidade.</em></h1>
            <p>Crie seu perfil e comece a descobrir um espaço feito para conexões de verdade.</p>
            <?php
            foreach ([
                'Faça novas amizades',
                'Encontre comunidades com seus interesses',
                'Tenha uma experiência simples e acolhedora'
            ] as $x):
                ?>
                <div class="ck"><span class="sq2"><?= icon('check', 15) ?></span><?= $x ?></div>
            <?php endforeach; ?>
        </div>
        <div class="r">
            <form class="ac" method="post"><?= csrf_field() ?><a class="bk" href="index.php">← Voltar</a>
                <h2>Criar conta</h2>
                <p class="mut">É rápido e gratuito.</p>
                <?php if (!empty($err)): ?>
                    <div class="card" style="padding:10px;border-color:#e5484d;color:#e5484d"><?= e($err) ?></div>
                <?php endif; ?>
                <label>Nome</label>
                <input name="name" required maxlength="100" placeholder="Seu nome" value="<?=e($_POST['name']??'')?>">
                <label>E-mail</label>
                <input name="email" type="email" required maxlength="255" autocomplete="email" placeholder="seu@email.com" value="<?=e($_POST['email']??'')?>">
                <label>Data de nascimento</label>
                <input name="birth_date" type="date" required max="<?=date('Y-m-d', strtotime('-18 years'))?>" value="<?=e($_POST['birth_date']??'')?>">
                <?= location_fields(strtoupper((string)($_POST['state'] ?? '')), (string)($_POST['city'] ?? '')) ?>
                <label>Senha</label>
                <input name="password" type="password" required minlength="10" autocomplete="new-password" placeholder="Mínimo de 10 caracteres">
                <label>Confirmar senha</label>
                <input name="confirm" type="password" required autocomplete="new-password" placeholder="Digite novamente">
                <label class="checkbox-line" style="margin-top:14px"><input type="checkbox" name="aceite" value="1" required> <span>Tenho 18 anos ou mais e aceito os <a class="lk2" href="termos.php" target="_blank">Termos</a> e a <a class="lk2" href="privacidade.php" target="_blank">Privacidade</a>.</span></label>
                <button class="btn wide" type="submit">Criar conta <?= icon('arr', 18) ?></button>
                <p class="mut" style="text-align:center">Já tem uma conta? <a class="lk2" href="login.php">Entrar</a></p>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require __DIR__.'/config/database.php';
require_once __DIR__.'/includes/totp.php';
if(user()) redirect('index.php');

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $name=trim($_POST['name']??'');
    $email=strtolower(trim($_POST['email']??''));
    $pass=$_POST['password']??'';
    $confirm=$_POST['confirm']??'';
    $birth=trim($_POST['birth_date']??'');
    $bd=DateTime::createFromFormat('!Y-m-d',$birth);
    $birthOk=$bd&&$bd->format('Y-m-d')===$birth&&$bd<=new DateTime('today');
    if(!$name||mb_strlen($name)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>255) $err='Informe nome e e-mail válidos.';
    elseif(!$birthOk) $err='Informe sua data de nascimento.';
    elseif(age($birth)<18) $err='O Lindr é exclusivo para maiores de 18 anos.';
    elseif($e2=password_error($pass)) $err=$e2;
    elseif($pass!==$confirm) $err='As senhas não coincidem.';
    elseif(empty($_POST['aceite'])) $err='Aceite os Termos de uso e a Política de privacidade para continuar.';
    else {
        $s=$pdo->prepare('SELECT id,verificado FROM usuarios WHERE email=?');
        $s->execute([$email]);
        if($s->fetch()) $err='Este e-mail já está cadastrado.';
        else {
            // verificado=0: o selo só deve aparecer depois de uma verificação real (e-mail/administração).
            try{
                $s=$pdo->prepare('INSERT INTO usuarios(nome,email,senha_hash,data_nascimento,verificado) VALUES(?,?,?,?,0)');
                $s->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$birth]);
            }catch(PDOException $e){
                if($e->getCode()!=='23000') throw $e;   // 23000 = e-mail duplicado (dois cadastros ao mesmo tempo)
                $err='Este e-mail já está cadastrado.';
            }
            if(!isset($err)){
                $id=(int)$pdo->lastInsertId();
                session_regenerate_id(true);
                if(tfa_obrigatorio()){
                    // conta nova: configura a verificação em duas etapas antes de entrar no site
                    $_SESSION['2fa_pendente']=['id'=>$id,'ate'=>time()+900];
                    flash('Conta criada! Falta só ativar a verificação em duas etapas.');
                    redirect('verificacao_2fa.php');
                }
                $_SESSION['user_id']=$id;
                flash('Conta criada com sucesso!');
                redirect('index.php');
            }
        }
    }
}
$page='cadastro';$title='Criar conta — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><div class="auth"><div class="l"><small class="ey lt">FAÇA PARTE</small><h1>Seu espaço.<br><em>Sua comunidade.</em></h1><p>Crie seu perfil e comece a descobrir um espaço feito para conexões de verdade.</p><?php foreach(['Faça novas amizades','Encontre comunidades com seus interesses','Tenha uma experiência simples e acolhedora'] as $x): ?><div class="ck"><span class="sq2"><?=icon('check',15)?></span><?=$x?></div><?php endforeach; ?></div><div class="r"><form class="ac" method="post"><?=csrf_field()?><a class="bk" href="index.php">← Voltar</a><h2>Criar conta</h2><p class="mut">É rápido e gratuito.</p><?php if(!empty($err)): ?><div class="card" style="padding:10px;border-color:#e5484d;color:#e5484d"><?=e($err)?></div><?php endif; ?><label>Nome</label><input name="name" required maxlength="100" placeholder="Seu nome" value="<?=e($_POST['name']??'')?>"><label>E-mail</label><input name="email" type="email" required maxlength="255" placeholder="seu@email.com" value="<?=e($_POST['email']??'')?>"><label>Data de nascimento</label><input name="birth_date" type="date" required max="<?=date('Y-m-d')?>" value="<?=e($_POST['birth_date']??'')?>"><label>Senha</label><input name="password" type="password" required minlength="10" autocomplete="new-password" placeholder="Mínimo de 10 caracteres"><label>Confirmar senha</label><input name="confirm" type="password" required autocomplete="new-password" placeholder="Digite novamente"><label class="checkbox-line" style="margin-top:14px"><input type="checkbox" name="aceite" value="1" required> <span>Tenho 18 anos ou mais e aceito os <a class="lk2" href="termos.php" target="_blank">Termos</a> e a <a class="lk2" href="privacidade.php" target="_blank">Privacidade</a>.</span></label><button class="btn wide" type="submit">Criar conta <?=icon('arr',18)?></button><p class="mut" style="text-align:center">Já tem uma conta? <a class="lk2" href="login.php">Entrar</a></p></form></div></div></div><?php require __DIR__.'/includes/footer.php'; ?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
