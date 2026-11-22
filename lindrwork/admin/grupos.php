<?php
declare(strict_types = 1);
require_once __DIR__ . '/../config/database.php';
$admin = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)$_POST['id'];
    $status = $_POST['status'] === 'cancelado'?'cancelado':'ativo';
    $s = $pdo->prepare('UPDATE grupos SET status=? WHERE id=?');
    $s->execute([$status, $id]);
    admin_log('alterou grupo', 'grupo', $id, 'status=' . $status);
    flash('Grupo atualizado.', 'success');
    redirect('grupos.php');
}
$rows = $pdo->query("SELECT g.*,u.nome criador,(SELECT COUNT(*) FROM grupo_membros gm WHERE gm.grupo_id=g.id) membros FROM grupos g JOIN usuarios u ON u.id=g.criador_id ORDER BY g.data_criacao DESC LIMIT 300")
    ->fetchAll();
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Grupos — Lindr</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/partials.php'; ?>
    <main class="admin-main">
        <h1>Comunidades e eventos</h1>
        <p class="muted">Visualize e modere os grupos criados na plataforma.</p>
        <?php if ($flash): ?>
            <div class="flash <?=$flash['type']?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <section class="card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Criador</th>
                            <th>Membros</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><strong><?= e($r['nome']) ?></strong><small><?= e($r['tema'] ?? '') ?></small></td>
                                <td><?= e($r['tipo']) ?></td>
                                <td><?= e($r['criador']) ?></td>
                                <td><?= $r['membros'] ?></td>
                                <td><?= date('d/m/Y', strtotime($r['data_criacao'])) ?></td>
                                <td>
                                    <form method="post" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?=$r['id']?>"><select name="status">
                                        <option value="ativo" <?=$r['status']==='ativo'?'selected':''?>>ativo</option>
                                        <option value="cancelado" <?=$r['status']==='cancelado'?'selected':''?>>cancelado</option>
                                        </select><button class="btn sm">Salvar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
