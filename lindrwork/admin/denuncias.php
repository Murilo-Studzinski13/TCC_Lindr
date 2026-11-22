<?php
declare(strict_types = 1);
require_once __DIR__ . '/../config/database.php';
$admin = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)$_POST['id'];
    $acao = (string)($_POST['acao'] ?? 'status');
    if ($acao === 'suspender') {
        $uid = (int)($_POST['usuario_id'] ?? 0);
        $s = $pdo->prepare("UPDATE usuarios SET ativo=0,discover_enabled=0 WHERE id=? AND nivel_acesso<>'admin' AND id<>?");
        $s->execute([$uid, (int)$admin['id']]);
        if ($s->rowCount()) {
            $pdo->prepare("UPDATE denuncias SET status='resolvida' WHERE id=?")->execute([$id]);
            admin_log('suspendeu usuário por denúncia', 'usuario', $uid, 'denuncia #' . $id);
            flash('Usuário suspenso e denúncia resolvida.', 'success');
        } else flash('Não foi possível suspender esta conta.', 'error');
        redirect('denuncias.php');
    }
    if ($acao === 'remover_post') {
        $pid = (int)($_POST['publicacao_id'] ?? 0);
        $pdo->prepare('DELETE FROM publicacoes WHERE id=?')->execute([$pid]);
        $pdo->prepare("UPDATE denuncias SET status='resolvida' WHERE id=?")->execute([$id]);
        admin_log('removeu publicação por denúncia', 'publicacao', $pid, 'denuncia #' . $id);
        flash('Publicação removida e denúncia resolvida.', 'success');
        redirect('denuncias.php');
    }
    $status = $_POST['status'] ?? 'aberta';
    if (!in_array($status, ['aberta', 'em_analise', 'resolvida', 'arquivada'], true)) $status = 'aberta';
    $s = $pdo->prepare('UPDATE denuncias SET status=? WHERE id=?');
    $s->execute([$status, $id]);
    admin_log('atualizou denúncia', 'denuncia', $id, 'status=' . $status);
    flash('Denúncia atualizada.', 'success');
    redirect('denuncias.php');
}
$status = $_GET['status'] ?? '';
$sql = "SELECT d.*, dn.nome denunciante_nome, dd.nome denunciado_nome, dd.ativo denunciado_ativo, dd.nivel_acesso denunciado_nivel, p.conteudo post_conteudo, m.conteudo mensagem_conteudo FROM denuncias d JOIN usuarios dn ON dn.id=d.denunciante_id JOIN usuarios dd ON dd.id=d.denunciado_id LEFT JOIN publicacoes p ON p.id=d.publicacao_id LEFT JOIN mensagens m ON m.id=d.mensagem_id WHERE 1";
$params = [];
if (in_array($status, ['aberta', 'em_analise', 'resolvida', 'arquivada'], true)) {
    $sql .= ' AND d.status=?';
    $params[] = $status;
}
$sql .= ' ORDER BY d.data_denuncia DESC LIMIT 300';
$s = $pdo->prepare($sql);
$s->execute($params);
$rows = $s->fetchAll();
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Denúncias — Lindr</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/partials.php'; ?>
    <main class="admin-main">
        <h1>Denúncias</h1>
        <p class="muted">Analise denúncias de usuários, publicações e mensagens.</p>
        <?php if ($flash): ?>
            <div class="flash <?=$flash['type']?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <section class="card">
            <form class="filters"><select name="status">
                <option value="">Todos</option>
                <?php foreach (['aberta', 'em_analise', 'resolvida', 'arquivada'] as $x): ?>
                    <option value="<?=$x?>" <?=$status===$x?'selected':''?>><?= $x ?></option>
                <?php endforeach; ?>
                </select><button class="btn">Filtrar</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Denunciante</th>
                            <th>Denunciado</th>
                            <th>Motivo</th>
                            <th>Conteúdo</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>#<?= $r['id'] ?></td>
                                <td><?= e($r['denunciante_nome']) ?></td>
                                <td><?= e($r['denunciado_nome']) ?></td>
                                <td><?= e($r['motivo']) ?></td>
                                <td><?= e(mb_strimwidth($r['post_conteudo'] ?? $r['mensagem_conteudo'] ?? $r['descricao'] ?? '—', 0, 100, '…')) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($r['data_denuncia'])) ?></td>
                                <td>
                                    <form method="post" class="inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?=$r['id']?>"><select name="status">
                                        <option <?=$r['status']==='aberta'?'selected':''?>>aberta</option>
                                        <option <?=$r['status']==='em_analise'?'selected':''?>>em_analise</option>
                                        <option <?=$r['status']==='resolvida'?'selected':''?>>resolvida</option>
                                        <option <?=$r['status']==='arquivada'?'selected':''?>>arquivada</option>
                                        </select><button class="btn sm">Salvar</button>
                                    </form>
                                </td>
                                <td>
                                    <?php if ($r['publicacao_id']): ?>
                                        <form method="post" class="inline" onsubmit="return confirm('Remover esta publicação?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="acao" value="remover_post"><input type="hidden" name="publicacao_id" value="<?=$r['publicacao_id']?>"><button class="btn sm danger">Remover post</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ((int)$r['denunciado_ativo'] === 1 && $r['denunciado_nivel'] !== 'admin'): ?>
                                        <form method="post" class="inline" onsubmit="return confirm('Suspender esta conta?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="acao" value="suspender"><input type="hidden" name="usuario_id" value="<?=$r['denunciado_id']?>"><button class="btn sm danger">Suspender usuário</button>
                                        </form>
                                    <?php elseif ((int)$r['denunciado_ativo'] !== 1): ?>
                                        <small class="muted">conta suspensa</small>
                                    <?php endif; ?>
                                </td>
                                <td><a class="btn sm" href="denuncia.php?id=<?=$r['id']?>">Ver o que aconteceu</a></td>
                            </tr>
                            <?php
                        endforeach;
                        if (!$rows):
                            ?>
                            <tr>
                                <td colspan="9">Nenhuma denúncia encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
