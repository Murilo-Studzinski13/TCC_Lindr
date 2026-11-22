<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$hash = hash('sha256', $token);
$s = $pdo->prepare('SELECT id,usuario_id FROM tokens_redefinicao_senha WHERE token_hash=? AND usado=0 AND expira_em>NOW() LIMIT 1');
$s->execute([$hash]);
$reset = $s->fetch();
if (!$reset) {
    http_response_code(400);
    $invalid = true;
} else $invalid = false;
if (!$invalid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pass = $_POST['senha'] ?? '';
    $confirm = $_POST['confirmar'] ?? '';
    if (password_error($pass) || $pass !== $confirm) {
        flash(password_error($pass) ?? 'A confirmação não confere com a nova senha.', 'error');
        redirect('senha_redefinir.php?token=' . urlencode($token));
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE usuarios SET senha_hash=? WHERE id=?')
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $reset['usuario_id']]);
        $pdo->prepare('UPDATE tokens_redefinicao_senha SET usado=1 WHERE usuario_id=?')
            ->execute([$reset['usuario_id']]);
        // senha nova: todos os aparelhos confiáveis voltam a pedir o código por e-mail
        $pdo->prepare('DELETE FROM dispositivos_confiaveis WHERE usuario_id=?')->execute([$reset['usuario_id']]);
        $pdo->commit();
        flash('Senha redefinida. Entre com sua nova senha.');
        redirect('login.php');
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
$page = 'login';
$title = 'Nova senha — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap" style="max-width:520px">
        <div class="card">
            <h1>Definir nova senha</h1>
            <?php if ($invalid): ?>
                <p>Este link é inválido ou expirou. Solicite uma nova redefinição.</p>
                <a class="btn" href="senha_esqueci.php">Solicitar novo link</a>
            <?php else: ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="token" value="<?=e($token)?>">
                    <label>Nova senha</label>
                    <input type="password" name="senha" minlength="10" required autocomplete="new-password">
                    <label>Confirmar nova senha</label>
                    <input type="password" name="confirmar" minlength="10" required autocomplete="new-password">
                    <p class="mut">Pelo menos 10 caracteres, incluindo letras e números.</p>
                    <div class="actions"><button class="btn">Salvar nova senha</button></div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require __DIR__.'/config/database.php';$token=trim($_GET['token']??$_POST['token']??'');$hash=hash('sha256',$token);$s=$pdo->prepare('SELECT id,usuario_id FROM tokens_redefinicao_senha WHERE token_hash=? AND usado=0 AND expira_em>NOW() LIMIT 1');$s->execute([$hash]);$reset=$s->fetch();if(!$reset){http_response_code(400);$invalid=true;}else $invalid=false;if(!$invalid&&$_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$pass=$_POST['senha']??'';$confirm=$_POST['confirmar']??'';if(password_error($pass)||$pass!==$confirm){flash(password_error($pass)??'A confirmação não confere com a nova senha.','error');redirect('senha_redefinir.php?token='.urlencode($token));}$pdo->beginTransaction();try{$pdo->prepare('UPDATE usuarios SET senha_hash=? WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),$reset['usuario_id']]);$pdo->prepare('UPDATE tokens_redefinicao_senha SET usado=1 WHERE usuario_id=?')->execute([$reset['usuario_id']]);$pdo->commit();flash('Senha redefinida. Entre com sua nova senha.');redirect('login.php');}catch(Throwable $e){$pdo->rollBack();throw $e;}}
$page='login';$title='Nova senha — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php';?>
<div class="pg"><main class="wrap" style="max-width:520px"><div class="card"><h1>Definir nova senha</h1><?php if($invalid):?><p>Este link é inválido ou expirou. Solicite uma nova redefinição.</p><a class="btn" href="senha_esqueci.php">Solicitar novo link</a><?php else:?><form method="post"><?=csrf_field()?><input type="hidden" name="token" value="<?=e($token)?>"><label>Nova senha</label><input type="password" name="senha" minlength="10" required autocomplete="new-password"><label>Confirmar nova senha</label><input type="password" name="confirmar" minlength="10" required autocomplete="new-password"><p class="mut">Pelo menos 10 caracteres, incluindo letras e números.</p><div class="actions"><button class="btn">Salvar nova senha</button></div></form><?php endif;?></div></main></div><?php require __DIR__.'/includes/footer.php';?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
