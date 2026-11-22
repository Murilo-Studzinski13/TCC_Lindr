<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/app_helpers.php';
require_once __DIR__ . '/includes/confirmacao_email.php';
$me = require_login();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'remover') {
        dispositivos_revogar($pdo, $uid, (int)($_POST['id'] ?? 0));
        flash('Dispositivo removido. Ele vai pedir o código de novo no próximo login.');
    } elseif ($action === 'remover_todos') {
        dispositivos_revogar($pdo, $uid);
        flash('Todos os dispositivos foram removidos.');
    }
    redirect('dispositivos.php');
}

$s = $pdo->prepare('SELECT id,nome,criado_em,ultimo_uso,expira_em FROM dispositivos_confiaveis WHERE usuario_id=? AND expira_em > NOW() ORDER BY COALESCE(ultimo_uso,criado_em) DESC');
$s->execute([$uid]);
$devices = $s->fetchAll();
$current = dispositivo_atual_id($pdo, $uid);

$page = 'configuracoes';
$title = 'Dispositivos confiáveis — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap" style="max-width:720px">
        <a class="bk" href="configuracoes.php">← Configurações</a>
        <h1>Dispositivos confiáveis</h1>
        <p class="mut">Ao entrar em um aparelho novo, o Lindr envia um código para o seu e-mail. Os aparelhos abaixo foram marcados como confiáveis e não pedem o código por <?= DISPOSITIVO_DIAS ?> dias.</p>
        <?php if (!$devices): ?>
            <div class="card"><p class="mut">Nenhum dispositivo confiável. No próximo login, marque "Confiar neste dispositivo" para não precisar do código toda vez.</p></div>
        <?php endif; ?>
        <?php foreach ($devices as $d): ?>
            <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <div>
                    <strong><?= e($d['nome']) ?></strong>
                    <?php if ($current === (int)$d['id']): ?>
                        <span class="pill">Este aparelho</span>
                    <?php endif; ?>
                    <div class="mut">Adicionado em <?= e(date('d/m/Y H:i', strtotime($d['criado_em']))) ?> · último uso <?= e(date('d/m/Y H:i', strtotime($d['ultimo_uso'] ?: $d['criado_em']))) ?> · vale até <?= e(date('d/m/Y', strtotime($d['expira_em']))) ?></div>
                </div>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remover"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn sm ghost" type="submit">Remover</button></form>
            </div>
        <?php endforeach; ?>
        <?php if ($devices): ?>
            <form method="post" onsubmit="return confirm('Remover todos os dispositivos confiáveis?')"><?= csrf_field() ?><input type="hidden" name="action" value="remover_todos"><button class="btn danger" type="submit">Remover todos</button></form>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
