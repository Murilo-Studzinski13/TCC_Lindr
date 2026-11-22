<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/app_helpers.php';
$me = require_login();
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'read_all') {
        $pdo->prepare('UPDATE notificacoes SET lida=1 WHERE usuario_id=?')->execute([$me['id']]);
        flash('Notificações marcadas como lidas.');
    } elseif ($action === 'read' && ctype_digit($_POST['id'] ?? '')) {
        $pdo->prepare('UPDATE notificacoes SET lida=1 WHERE id=? AND usuario_id=?')
            ->execute([(int)$_POST['id'], $me['id']]);
    }
    redirect('notificacoes.php');
}
$s = $pdo->prepare('SELECT * FROM notificacoes WHERE usuario_id=? ORDER BY criada_em DESC LIMIT 100');
$s->execute([$me['id']]);
$items = $s->fetchAll();
$unread = count(array_filter($items, fn($n) => (int)$n['lida'] === 0));
?>
<div class="row" style="justify-content:space-between">
    <div>
        <h1>Notificações</h1>
        <p class="mut">Acompanhe o que acontece na sua rede.</p>
    </div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read_all"><button class="btn secondary" <?=$unread?'':'disabled'?>>Marcar tudo como lido</button></form>
</div>
<div class="card">
    <?php if (!$items): ?>
        <div class="empty">Ainda não há notificações. Quando alguém interagir com você, elas aparecerão aqui.</div>
        <?php
    else:
        foreach ($items as $n):
            ?>
            <div class="result">
                <div style="font-size:22px"><?= $n['tipo'] === 'curtida'?'♥':($n['tipo'] === 'comentario'?'💬':($n['tipo'] === 'mensagem'?'✉':($n['tipo'] === 'match'?'✨':'🔔'))) ?></div>
                <div class="result-main"><b><?= e($n['titulo']) ?></b>
                    <?php if ($n['mensagem']): ?>
                        <p><?= e($n['mensagem']) ?></p>
                    <?php endif; ?>
                    <small class="mut"><?= e($n['criada_em']) ?></small>
                    <?php if ($n['link']): ?>
                        <p><a href="<?=e($n['link'])?>">Abrir</a></p>
                    <?php endif; ?>
                </div>
                <?php if (!(int)$n['lida']): ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?=$n['id']?>"><button class="btn secondary">Marcar lida</button></form>
                <?php else: ?>
                    <span class="pill">Lida</span>
                <?php endif; ?>
            </div>
            <?php
        endforeach;
    endif;
    ?>
</div>
<?php
$content = ob_get_clean();
$page = 'notificacoes';
$title = 'Notificações — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap"> <?= $content ?> </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
