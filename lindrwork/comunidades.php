<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/app_helpers.php';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $theme = trim($_POST['interest'] ?? '');
        $private = isset($_POST['private'])?1:0;
        $name = mb_substr($name, 0, 150);
        $desc = mb_substr($desc, 0, 2000);
        $theme = mb_substr($theme, 0, 80);
        // o tema é escolhido entre os interesses cadastrados (nada de texto livre)
        if ($theme !== '' && !in_array($theme, array_column(get_interests(), 'nome'), true)) $theme = '';
        if ($name) {
            $s = $pdo->prepare('INSERT INTO grupos(criador_id,tipo,nome,descricao,tema,privada) VALUES(?,"comunidade",?,?,?,?)');
            $s->execute([$me['id'], $name, $desc, $theme?:null, $private]);
            $gid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO grupo_membros(grupo_id,usuario_id,papel) VALUES(?,?,"admin")')
                ->execute([$gid, $me['id']]);
            flash('Comunidade criada.');
        }
    } elseif ($action === 'toggle') {
        $g = $pdo->prepare('SELECT id,privada,criador_id FROM grupos WHERE id=? AND status="ativo"');
        $g->execute([$id]);
        $grp = $g->fetch();
        if (!$grp) {
            flash('Grupo não encontrado.', 'error');
        } else {
            $s = $pdo->prepare('SELECT 1 FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');
            $s->execute([$id, $me['id']]);
            if ($s->fetchColumn()) {
                if ((int)$grp['criador_id'] === (int)$me['id']) flash('Quem criou o grupo não pode sair dele.', 'error');
                else $pdo->prepare('DELETE FROM grupo_membros WHERE grupo_id=? AND usuario_id=?')->execute([$id, $me['id']]);
            } elseif ((int)$grp['privada'] === 1) {
                flash('Este grupo é privado: só é possível entrar por convite.', 'error');
            } else $pdo->prepare('INSERT IGNORE INTO grupo_membros(grupo_id,usuario_id) VALUES(?,?)')->execute([$id, $me['id']]);
        }
        if (($_POST['from'] ?? '') === 'grupo') redirect('grupo.php?id=' . $id);
    }
    redirect('comunidades.php');
}
$com = $pdo->query('SELECT g.*,COUNT(gm.usuario_id) members FROM grupos g LEFT JOIN grupo_membros gm ON gm.grupo_id=g.id WHERE g.tipo="comunidade" AND g.status="ativo" AND (g.privada=0 OR EXISTS (SELECT 1 FROM grupo_membros mine WHERE mine.grupo_id=g.id AND mine.usuario_id=' . (int)$me['id'] . ')) GROUP BY g.id ORDER BY g.id DESC')
    ->fetchAll();
$page = 'comunidades';
$title = 'Comunidades — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap">
        <div class="row" style="justify-content:space-between">
            <h2>Comunidades</h2>
            <button class="btn sm" onclick="document.getElementById('newCommunity').showModal()">Criar comunidade</button>
        </div>
        <div class="grid" style="margin-top:18px">
            <?php
            foreach ($com as $c):
                $q = $pdo->prepare('SELECT 1 FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');
                $q->execute([$c['id'], $me['id']]);
                $joined = (bool)$q->fetchColumn();
                ?>
                <div class="card">
                    <h3><a style="color:inherit;text-decoration:none" href="grupo.php?id=<?=$c['id']?>"><?= e($c['nome']) ?></a> <?= $c['privada']?icon('lock', 15):'' ?></h3>
                    <p class="mut"><?= e($c['descricao']) ?></p>
                    <?php if ($c['tema']) echo chip($c['tema']); ?>
                    <p class="mut"><?= $c['members'] ?> membros</p>
                    <p><a href="grupo.php?id=<?=$c['id']?>">Abrir comunidade →</a></p>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$c['id']?>"><button class="btn sm <?=$joined?'ghost':''?>" type="submit"><?= $joined?'Sair':'Entrar' ?></button></form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<dialog id="newCommunity" class="card" style="border:0;max-width:420px;width:90%">
    <form method="post"><input type="hidden" name="action" value="create"><?= csrf_field() ?>
        <h3>Nova comunidade</h3>
        <label>Nome</label>
        <input name="name" required>
        <label>Descrição</label>
        <textarea name="description" rows="3"></textarea>
        <label>Interesse</label>
        <select name="interest">
            <option value="">Sem tema específico</option>
            <?php foreach (get_interests() as $in): ?>
                <option value="<?=e($in['nome'])?>"><?= e($in['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <label><input type="checkbox" name="private" style="width:auto"> Comunidade privada</label>
        <div class="acts"><button class="btn ghost" type="button" onclick="this.closest('dialog').close()">Cancelar</button><button class="btn">Criar</button></div>
    </form>
</dialog>
<?php require __DIR__ . '/includes/footer.php'; ?>
