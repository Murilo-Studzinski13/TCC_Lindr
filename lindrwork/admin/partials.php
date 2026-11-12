<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<header class="admin-top">
    <a class="admin-logo" href="../index.php">Lindr</a>

    <div class="admin-top-right">
        <span class="admin-user"><?=e(display_name($admin))?></span>
        <a class="admin-top-link" href="../index.php">Site</a>
        <form method="post" action="../logout.php" style="display:inline"><?=csrf_field()?><button class="admin-top-button" type="submit">Sair</button></form>
    </div>
</header>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-title">PAINEL ADMINISTRATIVO</div>

        <nav class="sidebar-nav">
            <a class="<?=$current === 'index.php' ? 'active' : ''?>" href="index.php">Visão geral</a>
            <a class="<?=$current === 'usuarios.php' ? 'active' : ''?>" href="usuarios.php">Usuários</a>
            <a class="<?=$current === 'denuncias.php' ? 'active' : ''?>" href="denuncias.php">Denúncias</a>
            <a class="<?=$current === 'publicacoes.php' ? 'active' : ''?>" href="publicacoes.php">Publicações</a>
            <a class="<?=$current === 'grupos.php' ? 'active' : ''?>" href="grupos.php">Comunidades e eventos</a>
        </nav>

        <div class="sidebar-bottom">
            <a href="../index.php">Voltar para o site</a>
        </div>
    </aside>
