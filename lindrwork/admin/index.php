<?php
declare(strict_types = 1);
require_once __DIR__ . '/../config/database.php';
$admin = require_admin();

$stats = [
    'usuarios' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'ativos' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1")->fetchColumn(),
    'posts' => (int)$pdo->query("SELECT COUNT(*) FROM publicacoes")->fetchColumn(),
    'denuncias' => (int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE status IN ('aberta','em_analise')")->fetchColumn(),
    'grupos' => (int)$pdo->query("SELECT COUNT(*) FROM grupos WHERE status='ativo'")->fetchColumn(),
    'mensagens' => (int)$pdo->query("SELECT COUNT(*) FROM mensagens")->fetchColumn(),
];
$recent = $pdo->query("SELECT a.*, u.nome AS admin_nome FROM auditoria_admin a JOIN usuarios u ON u.id=a.admin_id ORDER BY a.data_acao DESC LIMIT 10")
    ->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Painel administrativo — Lindr</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php $current = 'index.php'; ?>
    <header class="admin-top"> <a class="admin-logo" href="../index.php">Lindr</a>
        <div class="admin-top-right"> <span class="admin-user">Administrador: <?= e(display_name($admin)) ?></span> <a class="admin-top-link" href="../index.php">Site</a>
            <form method="post" action="../logout.php" style="display:inline"><?= csrf_field() ?><button class="admin-top-button" type="submit">Sair</button></form>
        </div>
    </header>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="sidebar-title">PAINEL ADMINISTRATIVO</div>
            <nav class="sidebar-nav"> <a class="active" href="index.php">Visão geral</a> <a href="usuarios.php">Usuários</a> <a href="denuncias.php">Denúncias</a> <a href="publicacoes.php">Publicações</a> <a href="grupos.php">Comunidades e eventos</a> </nav>
            <div class="sidebar-bottom"><a href="../index.php">Voltar para o site</a></div>
        </aside>
        <main class="admin-main">
            <h1>Painel administrativo</h1>
            <p class="muted">Controle de usuários, conteúdo, denúncias e atividades do Lindr.</p>
            <section class="stats">
                <div><b><?= $stats['usuarios'] ?></b><span>Usuários</span></div>
                <div><b><?= $stats['ativos'] ?></b><span>Usuários ativos</span></div>
                <div><b><?= $stats['posts'] ?></b><span>Publicações</span></div>
                <div class="warn"><b><?= $stats['denuncias'] ?></b><span>Denúncias pendentes</span></div>
                <div><b><?= $stats['grupos'] ?></b><span>Grupos ativos</span></div>
                <div><b><?= $stats['mensagens'] ?></b><span>Mensagens</span></div>
            </section>
            <section class="card">
                <div class="section-head">
                    <h2>Atalhos</h2>
                </div>
                <div class="quick"><a href="usuarios.php?action=new">+ Cadastrar usuário</a><a href="usuarios.php">Gerenciar usuários</a><a href="denuncias.php">Ver denúncias</a><a href="publicacoes.php">Moderar publicações</a><a href="grupos.php">Gerenciar grupos</a></div>
            </section>
            <section class="card">
                <div class="section-head">
                    <h2>Atividade administrativa</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Ação</th>
                                <th>Administrador</th>
                                <th>Entidade</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $r): ?>
                                <tr>
                                    <td><?= e($r['acao']) ?></td>
                                    <td><?= e($r['admin_nome']) ?></td>
                                    <td><?= e(($r['entidade'] ?? '—') . ($r['entidade_id']?' #' . $r['entidade_id']:'')) ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime($r['data_acao']))) ?></td>
                                </tr>
                                <?php
                            endforeach;
                            if (!$recent):
                                ?>
                                <tr>
                                    <td colspan="4">Nenhuma atividade registrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
