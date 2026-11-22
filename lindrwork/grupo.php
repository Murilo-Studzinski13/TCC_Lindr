<?php
<<<<<<< HEAD
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/app_helpers.php';
require_once __DIR__ . '/includes/locais.php';
$me = require_login();
ob_start();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('comunidades.php');
$s = $pdo->prepare("SELECT g.*,u.nome AS criador_nome,u.nome_social AS criador_social FROM grupos g JOIN usuarios u ON u.id=g.criador_id WHERE g.id=?");
$s->execute([$id]);
$group = $s->fetch();
if (!$group || ($group['status'] !== 'ativo' && $group['tipo'] === 'comunidade')) {
    http_response_code(404);
    exit('Comunidade ou evento não encontrado.');
}
$member = $pdo->prepare('SELECT papel FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');
$member->execute([$id, $me['id']]);
$role = $member->fetchColumn();
if ((int)$group['privada'] === 1 && !$role && (int)$group['criador_id'] !== (int)$me['id']) {
    http_response_code(403);
    exit('Esta comunidade é privada. Participe por um convite ou solicite acesso ao administrador.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($group['status'] !== 'ativo') {
        flash('Este evento foi cancelado.', 'error');
        redirect('grupo.php?id=' . $id);
    }
    if (!$role) {
        flash('Participe para publicar ou comentar.', 'error');
        redirect('grupo.php?id=' . $id);
    }
    if ($action === 'post' && $group['tipo'] === 'comunidade') {
        $text = trim($_POST['conteudo'] ?? '');
        if ($text !== '' && mb_strlen($text) <= 3000) {
            $pdo->prepare('INSERT INTO publicacoes_grupos(grupo_id,usuario_id,conteudo) VALUES(?,?,?)')
                ->execute([$id, $me['id'], $text]);
            flash('Publicação adicionada à comunidade.');
        } else flash('Escreva uma publicação de até 3000 caracteres.', 'error');
    } elseif ($action === 'comment' && $group['tipo'] === 'evento') {
        $text = trim($_POST['conteudo'] ?? '');
        if ($text !== '' && mb_strlen($text) <= 1500) {
            $pdo->prepare('INSERT INTO comentarios_eventos(grupo_id,usuario_id,conteudo) VALUES(?,?,?)')
                ->execute([$id, $me['id'], $text]);
            flash('Comentário adicionado ao evento.');
        } else flash('Escreva um comentário de até 1500 caracteres.', 'error');
    }
    redirect('grupo.php?id=' . $id);
}
$s = $pdo->prepare('SELECT COUNT(*) FROM grupo_membros WHERE grupo_id=?');
$s->execute([$id]);
$members = (int)$s->fetchColumn();
$s = $pdo->prepare('SELECT u.id,u.nome,u.nome_social,u.foto_url,p.conteudo,p.imagem_url,p.criada_em FROM publicacoes_grupos p JOIN usuarios u ON u.id=p.usuario_id WHERE p.grupo_id=? ORDER BY p.criada_em DESC LIMIT 50');
$s->execute([$id]);
$posts = $s->fetchAll();
$s = $pdo->prepare('SELECT u.id,u.nome,u.nome_social,u.foto_url,c.conteudo,c.criada_em FROM comentarios_eventos c JOIN usuarios u ON u.id=c.usuario_id WHERE c.grupo_id=? ORDER BY c.criada_em DESC LIMIT 50');
$s->execute([$id]);
$comments = $s->fetchAll();
?>
<div class="card"><span class="pill"><?= $group['tipo'] === 'evento'?'Evento':'Comunidade' ?></span>
    <h1><?= e($group['nome']) ?></h1>
    <p><?= e($group['descricao'] ?? '') ?></p>
    <?php if ($group['tema']): ?>
        <span class="pill"><?= e($group['tema']) ?></span>
    <?php endif; ?>
    <?php if ($group['tipo'] === 'evento'): ?>
        <p><b>Quando:</b> <?= e($group['data_evento']?date('d/m/Y H:i', strtotime($group['data_evento'])):'A definir') ?> <br><b>Onde:</b> <?= e(event_place($group) ?: 'A definir') ?></p>
    <?php endif; ?>
    <p class="mut"><?= $members ?> participantes · criado por <?= e($group['criador_social']?:$group['criador_nome']) ?></p>
    <?php if ($group['status'] !== 'ativo'): ?>
        <p class="notice">Este evento foi cancelado.</p>
    <?php elseif (!$role): ?>
        <form method="post" action="<?=$group['tipo']==='evento'?'eventos.php':'comunidades.php'?>"><?= csrf_field() ?><input type="hidden" name="from" value="grupo"><input type="hidden" name="action" value="<?=$group['tipo']==='evento'?'join':'toggle'?>"><input type="hidden" name="id" value="<?=$id?>"><button class="btn">Participar</button></form>
    <?php else: ?>
        <span class="pill">Você participa</span>
    <?php endif; ?>
</div>
<?php if ($group['tipo'] === 'comunidade'): ?>
    <h2>Publicações da comunidade</h2>
    <?php if ($role && $group['status'] === 'ativo'): ?>
        <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="post">
            <label>Compartilhe algo com a comunidade</label>
            <textarea name="conteudo" maxlength="3000" rows="3" required></textarea>
            <div class="actions"><button class="btn">Publicar</button></div>
        </form>
    <?php endif; ?>
    <section class="card">
        <?php if (!$posts): ?>
            <div class="empty">Ainda não há publicações nesta comunidade.</div>
            <?php
        else:
            foreach ($posts as $post):
                ?>
                <article class="feed-post">
                    <div class="person"><?= avatar(['id' => $post['id'], 'nome' => $post['nome'], 'nome_social' => $post['nome_social'], 'foto_url' => $post['foto_url']]) ?>
                        <div><b><?= e($post['nome_social']?:$post['nome']) ?></b>
                            <div class="mut"><?= e(date('d/m/Y H:i', strtotime($post['criada_em']))) ?></div>
                        </div>
                    </div>
                    <p class="post-content"><?= e($post['conteudo']) ?></p>
                    <?php if ($post['imagem_url']): ?>
                        <img class="thumb" src="<?=e($post['imagem_url'])?>" alt="Imagem da publicação">
                    <?php endif; ?>
                </article>
                <?php
            endforeach;
        endif;
        ?>
    </section>
<?php else: ?>
    <h2>Conversa do evento</h2>
    <?php if ($role && $group['status'] === 'ativo'): ?>
        <form class="card" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="comment">
            <label>Deixe um comentário</label>
            <textarea name="conteudo" maxlength="1500" rows="3" required></textarea>
            <div class="actions"><button class="btn">Comentar</button></div>
        </form>
    <?php endif; ?>
    <section class="card">
        <?php if (!$comments): ?>
            <div class="empty">Ainda não há comentários neste evento.</div>
            <?php
        else:
            foreach ($comments as $c):
                ?>
                <article class="feed-post">
                    <div class="person"><?= avatar(['id' => $c['id'], 'nome' => $c['nome'], 'nome_social' => $c['nome_social'], 'foto_url' => $c['foto_url']]) ?>
                        <div><b><?= e($c['nome_social']?:$c['nome']) ?></b>
                            <div class="mut"><?= e(date('d/m/Y H:i', strtotime($c['criada_em']))) ?></div>
                        </div>
                    </div>
                    <p><?= e($c['conteudo']) ?></p>
                </article>
                <?php
            endforeach;
        endif;
        ?>
    </section>
<?php endif; ?>
<?php
$content = ob_get_clean();
$page = 'grupo';
$title = 'Comunidade — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap"> <?= $content ?> </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
$me=require_login();
ob_start();

$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$id)redirect('comunidades.php');$s=$pdo->prepare("SELECT g.*,u.nome AS criador_nome,u.nome_social AS criador_social FROM grupos g JOIN usuarios u ON u.id=g.criador_id WHERE g.id=?");$s->execute([$id]);$group=$s->fetch();if(!$group||($group['status']!=='ativo'&&$group['tipo']==='comunidade')){http_response_code(404);exit('Comunidade ou evento não encontrado.');}$member=$pdo->prepare('SELECT papel FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');$member->execute([$id,$me['id']]);$role=$member->fetchColumn();if((int)$group['privada']===1 && !$role && (int)$group['criador_id']!==(int)$me['id']){http_response_code(403);exit('Esta comunidade é privada. Participe por um convite ou solicite acesso ao administrador.');}
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$action=$_POST['action']??'';if($group['status']!=='ativo'){flash('Este evento foi cancelado.','error');redirect('grupo.php?id='.$id);}if(!$role){flash('Participe para publicar ou comentar.','error');redirect('grupo.php?id='.$id);}if($action==='post'&&$group['tipo']==='comunidade'){$text=trim($_POST['conteudo']??'');if($text!==''&&mb_strlen($text)<=3000){$pdo->prepare('INSERT INTO publicacoes_grupos(grupo_id,usuario_id,conteudo) VALUES(?,?,?)')->execute([$id,$me['id'],$text]);flash('Publicação adicionada à comunidade.');}else flash('Escreva uma publicação de até 3000 caracteres.','error');}elseif($action==='comment'&&$group['tipo']==='evento'){$text=trim($_POST['conteudo']??'');if($text!==''&&mb_strlen($text)<=1500){$pdo->prepare('INSERT INTO comentarios_eventos(grupo_id,usuario_id,conteudo) VALUES(?,?,?)')->execute([$id,$me['id'],$text]);flash('Comentário adicionado ao evento.');}else flash('Escreva um comentário de até 1500 caracteres.','error');}redirect('grupo.php?id='.$id);}
$s=$pdo->prepare('SELECT COUNT(*) FROM grupo_membros WHERE grupo_id=?');$s->execute([$id]);$members=(int)$s->fetchColumn();$s=$pdo->prepare('SELECT u.id,u.nome,u.nome_social,u.foto_url,p.conteudo,p.imagem_url,p.criada_em FROM publicacoes_grupos p JOIN usuarios u ON u.id=p.usuario_id WHERE p.grupo_id=? ORDER BY p.criada_em DESC LIMIT 50');$s->execute([$id]);$posts=$s->fetchAll();$s=$pdo->prepare('SELECT u.id,u.nome,u.nome_social,u.foto_url,c.conteudo,c.criada_em FROM comentarios_eventos c JOIN usuarios u ON u.id=c.usuario_id WHERE c.grupo_id=? ORDER BY c.criada_em DESC LIMIT 50');$s->execute([$id]);$comments=$s->fetchAll();
?>
<div class="card"><span class="pill"><?=$group['tipo']==='evento'?'Evento':'Comunidade'?></span><h1><?=e($group['nome'])?></h1><p><?=e($group['descricao']??'')?></p><?php if($group['tema']):?><span class="pill"><?=e($group['tema'])?></span><?php endif;?><?php if($group['tipo']==='evento'):?><p><b>Quando:</b> <?=e($group['data_evento']?date('d/m/Y H:i',strtotime($group['data_evento'])):'A definir')?> <br><b>Onde:</b> <?=e($group['local_evento']??'A definir')?></p><?php endif;?><p class="mut"><?=$members?> participantes · criado por <?=e($group['criador_social']?:$group['criador_nome'])?></p><?php if($group['status']!=='ativo'):?><p class="notice">Este evento foi cancelado.</p><?php elseif(!$role):?><form method="post" action="<?=$group['tipo']==='evento'?'eventos.php':'comunidades.php'?>"><?=csrf_field()?><input type="hidden" name="from" value="grupo"><input type="hidden" name="action" value="<?=$group['tipo']==='evento'?'join':'toggle'?>"><input type="hidden" name="id" value="<?=$id?>"><button class="btn">Participar</button></form><?php else:?><span class="pill">Você participa</span><?php endif;?></div>
<?php if($group['tipo']==='comunidade'):?><h2>Publicações da comunidade</h2><?php if($role&&$group['status']==='ativo'):?><form class="card" method="post"><?=csrf_field()?><input type="hidden" name="action" value="post"><label>Compartilhe algo com a comunidade</label><textarea name="conteudo" maxlength="3000" rows="3" required></textarea><div class="actions"><button class="btn">Publicar</button></div></form><?php endif;?><section class="card"><?php if(!$posts):?><div class="empty">Ainda não há publicações nesta comunidade.</div><?php else:foreach($posts as $post):?><article class="feed-post"><div class="person"><?=avatar(['id'=>$post['id'],'nome'=>$post['nome'],'nome_social'=>$post['nome_social'],'foto_url'=>$post['foto_url']])?><div><b><?=e($post['nome_social']?:$post['nome'])?></b><div class="mut"><?=e(date('d/m/Y H:i',strtotime($post['criada_em'])))?></div></div></div><p class="post-content"><?=e($post['conteudo'])?></p><?php if($post['imagem_url']):?><img class="thumb" src="<?=e($post['imagem_url'])?>" alt="Imagem da publicação"><?php endif;?></article><?php endforeach;endif;?></section><?php else:?><h2>Conversa do evento</h2><?php if($role&&$group['status']==='ativo'):?><form class="card" method="post"><?=csrf_field()?><input type="hidden" name="action" value="comment"><label>Deixe um comentário</label><textarea name="conteudo" maxlength="1500" rows="3" required></textarea><div class="actions"><button class="btn">Comentar</button></div></form><?php endif;?><section class="card"><?php if(!$comments):?><div class="empty">Ainda não há comentários neste evento.</div><?php else:foreach($comments as $c):?><article class="feed-post"><div class="person"><?=avatar(['id'=>$c['id'],'nome'=>$c['nome'],'nome_social'=>$c['nome_social'],'foto_url'=>$c['foto_url']])?><div><b><?=e($c['nome_social']?:$c['nome'])?></b><div class="mut"><?=e(date('d/m/Y H:i',strtotime($c['criada_em'])))?></div></div></div><p><?=e($c['conteudo'])?></p></article><?php endforeach;endif;?></section><?php endif;?>

<?php
$content=ob_get_clean();
$page='grupo'; $title='Comunidade — Lindr'; require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap">
<?= $content ?>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
