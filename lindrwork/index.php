<?php
require __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';

$me = user();

if ($me && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'publicar') {
        $conteudo = trim($_POST['conteudo'] ?? '');
        $tema = trim($_POST['tema'] ?? '');
        $imagem = trim($_POST['imagem_url'] ?? '');
        if ($conteudo === '') {
            flash('Escreva alguma coisa antes de publicar.', 'error');
        } elseif (mb_strlen($conteudo) > 3000 || mb_strlen($tema) > 80) {
            flash('A publicação pode ter até 3000 caracteres e o tema até 80.', 'error');
        } elseif ($imagem !== '' && !valid_image_url($imagem)) {
            flash('A URL da foto precisa começar com http:// ou https://.', 'error');
        } else {
            $s = $pdo->prepare('INSERT INTO publicacoes(usuario_id,conteudo,tema,imagem_url) VALUES(?,?,?,?)');
            $s->execute([$me['id'],$conteudo,$tema !== '' ? $tema : null,$imagem !== '' ? $imagem : null]);
            flash('Publicação criada!');
        }
        redirect('index.php');
    }

    if ($action === 'curtir') {
        $postId = (int)($_POST['publicacao_id'] ?? 0);
        $ex = $pdo->prepare('SELECT 1 FROM publicacoes WHERE id=?'); $ex->execute([$postId]);
        if (!$ex->fetchColumn()) { flash('Publicação não encontrada.', 'error'); redirect('index.php'); }
        $s = $pdo->prepare("SELECT id FROM interacoes WHERE publicacao_id=? AND usuario_id=? AND tipo='curtida' LIMIT 1");
        $s->execute([$postId,$me['id']]);
        $like = $s->fetchColumn();
        if ($like) {
            $pdo->prepare('DELETE FROM interacoes WHERE id=?')->execute([$like]);
        } else {
            $pdo->prepare("INSERT INTO interacoes(publicacao_id,usuario_id,tipo,curtida_unica) VALUES(?,?, 'curtida',1)")->execute([$postId,$me['id']]);
            $ownerQ=$pdo->prepare('SELECT usuario_id FROM publicacoes WHERE id=?');$ownerQ->execute([$postId]);$owner=(int)$ownerQ->fetchColumn();if($owner && $owner!==(int)$me['id'])notify_user($owner,'curtida','Sua publicação recebeu uma curtida',display_name($me).' curtiu sua publicação.','index.php#comentarios-'.$postId,(int)$me['id']);
        }
        redirect('index.php#post-'.$postId);
    }

    if ($action === 'comentar') {
        $postId = (int)($_POST['publicacao_id'] ?? 0);
        $comentario = mb_substr(trim($_POST['comentario'] ?? ''), 0, 1000);
        $ex = $pdo->prepare('SELECT 1 FROM publicacoes WHERE id=?'); $ex->execute([$postId]);
        if (!$ex->fetchColumn()) { flash('Publicação não encontrada.', 'error'); redirect('index.php'); }
        if ($comentario !== '') {
            $pdo->prepare("INSERT INTO interacoes(publicacao_id,usuario_id,tipo,conteudo) VALUES(?,?, 'comentario',?)")->execute([$postId,$me['id'],$comentario]);
            $ownerQ=$pdo->prepare('SELECT usuario_id FROM publicacoes WHERE id=?');$ownerQ->execute([$postId]);$owner=(int)$ownerQ->fetchColumn();if($owner && $owner!==(int)$me['id'])notify_user($owner,'comentario','Novo comentário na sua publicação',display_name($me).' comentou na sua publicação.','index.php#comentarios-'.$postId,(int)$me['id']);
        }
        redirect('index.php#post-'.$postId);
    }
}

$page='index';
$title='Lindr — amizades LGBTQIA+';
require __DIR__.'/includes/header.php';
require __DIR__.'/includes/navbar.php';
?>

<?php if (!$me): ?>
<div class="pg">
    <section class="hero">
        <div class="hl">
            <small class="ey">Um espaço para ser você</small>
            <h1>Conecte-se com pessoas que <em>entendem você.</em></h1>
            <p class="lead">O Lindr é um espaço pensado para amizades, comunidades e conexões LGBTQIA+ de forma leve, acolhedora e segura.</p>
            <div class="row" style="gap:24px;margin-top:34px;flex-wrap:wrap"><a class="btn big" href="cadastro.php">Criar minha conta <?=icon('arr',20)?></a><a class="lk" href="login.php">Já tenho uma conta</a></div>
            <div class="bl"><span>● Comunidade feita para acolher</span><span>● Apenas para maiores de 18 anos</span></div>
        </div>
        <div class="hr"><div class="pw"><div class="card phone"><div class="row" style="justify-content:space-between"><b style="color:var(--pri)">Lindr</b><?=icon('more',18)?></div><div class="pimg2"></div><h3>Alex, 18</h3><p class="mut">📍 3 km de distância</p><div><?=chip('música')?><?=chip('games')?><?=chip('amizade')?></div></div><div class="fl a"><b>Nova conexão!</b><small>Vocês têm interesses em comum</small></div></div></div>
    </section>
    <section class="feat" id="rec"><h2>Tudo para fazer amizade de verdade</h2><div class="grid"><?php foreach([['heart','Matches de amizade','Curta perfis e converse quando os dois quiserem.'],['msg','Chat','Mensagens só com quem deu match com você.'],['users','Comunidades','Grupos por interesse: games, livros, RPG e mais.'],['cal','Eventos','Crie ou confirme presença em encontros da galera.'],['lock','Privacidade','Você decide quem vê sua identidade e orientação.'],['shield','Segurança','Denuncie ou bloqueie em um toque. Só para maiores de 18.']] as $f): ?><div class="card"><div class="sq"><?=icon($f[0],18)?></div><h3><?=$f[1]?></h3><p class="mut"><?=$f[2]?></p></div><?php endforeach; ?></div></section>
</div>
<?php else: ?>
<div class="pg">
<section class="wrap feed-page">
    <div class="feed-head">
        <div><h2>Explorar</h2><p class="mut">Veja o que a galera está compartilhando.</p></div>
    </div>

    <div class="card composer">
        <form method="post">
            <?=csrf_field()?>
            <input type="hidden" name="action" value="publicar">
            <textarea name="conteudo" rows="3" placeholder="Compartilhe algo com a comunidade"></textarea>
            <div class="composer-bottom">
                <input name="tema" placeholder="Tema: games, música, RPG...">
                <input name="imagem_url" placeholder="URL da foto (opcional)">
                <button class="btn sm" type="submit">Publicar</button>
            </div>
        </form>
    </div>

    <div class="feed-list">
    <?php
    $sql = "SELECT p.*, u.id AS autor_id, u.nome, u.nome_social, u.foto_url, u.cidade, u.estado, u.verificado,
                   (SELECT COUNT(*) FROM interacoes i WHERE i.publicacao_id=p.id AND i.tipo='curtida') AS total_likes,
                   (SELECT COUNT(*) FROM interacoes i WHERE i.publicacao_id=p.id AND i.tipo='comentario') AS total_comentarios,
                   EXISTS(SELECT 1 FROM interacoes i WHERE i.publicacao_id=p.id AND i.usuario_id=? AND i.tipo='curtida') AS curtiu
            FROM publicacoes p
            INNER JOIN usuarios u ON u.id=p.usuario_id
            WHERE u.ativo=1
              AND NOT EXISTS (SELECT 1 FROM bloqueios b WHERE (b.usuario_id=? AND b.bloqueado_id=p.usuario_id) OR (b.usuario_id=p.usuario_id AND b.bloqueado_id=?))
            ORDER BY p.data_publicacao DESC
            LIMIT 50";
    $s=$pdo->prepare($sql); $s->execute([$me['id'],$me['id'],$me['id']]); $posts=$s->fetchAll();

    if (!$posts): ?>
        <div class="card empty-feed"><h3>Ainda não há publicações</h3><p class="mut">Seja a primeira pessoa a compartilhar alguma coisa com a comunidade.</p></div>
    <?php endif;

    foreach ($posts as $post):
        $autor = [
            'id'=>$post['autor_id'],'nome'=>$post['nome'],'nome_social'=>$post['nome_social'],
            'foto_url'=>$post['foto_url'],'cidade'=>$post['cidade'],'estado'=>$post['estado']
        ];
    ?>
        <article class="card post" id="post-<?=$post['id']?>">
            <div class="post-top">
                <div class="row author-info">
                    <?=avatar($autor)?>
                    <div><strong><?=e(display_name($autor))?></strong><div class="mut"><?=e($post['cidade'] ? $post['cidade'].($post['estado']?', '.$post['estado']:'') : 'Comunidade Lindr')?> · <?=e(date('d/m/Y H:i',strtotime($post['data_publicacao'])))?></div></div>
                </div>
                <?php if ((int)$post['autor_id']===(int)$me['id']): ?><span class="chip on">Minha publicação</span><?php endif; ?>
            </div>

            <?php if ($post['tema']): ?><div class="post-theme"><?=chip($post['tema'])?></div><?php endif; ?>
            <p class="post-text"><?=nl2br(e($post['conteudo']))?></p>

            <?php if ($post['imagem_url']): ?>
                <img class="post-photo" src="<?=e($post['imagem_url'])?>" alt="Foto da publicação" loading="lazy">
            <?php endif; ?>

            <div class="post-actions">
                <form method="post">
                    <?=csrf_field()?>
                    <input type="hidden" name="action" value="curtir">
                    <input type="hidden" name="publicacao_id" value="<?=$post['id']?>">
                    <button class="ab <?=$post['curtiu']?'on':''?>" type="submit"><?=icon('heart',18)?> <?=$post['total_likes']?></button>
                </form>
                <a class="ab" href="#comentarios-<?=$post['id']?>"><?=icon('msg',18)?> <?=$post['total_comentarios']?></a>
                <?php if ((int)$post['autor_id']!==(int)$me['id']): ?><a class="ab" href="denunciar.php?usuario_id=<?=$post['autor_id']?>&publicacao_id=<?=$post['id']?>">Denunciar</a><?php endif; ?>
            </div>

            <div class="comments" id="comentarios-<?=$post['id']?>">
                <?php
                $cs=$pdo->prepare("SELECT i.conteudo,i.data_interacao,u.id,u.nome,u.nome_social,u.foto_url FROM interacoes i INNER JOIN usuarios u ON u.id=i.usuario_id WHERE i.publicacao_id=? AND i.tipo='comentario' AND NOT EXISTS (SELECT 1 FROM bloqueios b WHERE (b.usuario_id=? AND b.bloqueado_id=i.usuario_id) OR (b.usuario_id=i.usuario_id AND b.bloqueado_id=?)) ORDER BY i.data_interacao ASC");
                $cs->execute([$post['id'],$me['id'],$me['id']]); $comments=$cs->fetchAll();
                foreach($comments as $comment):
                    $cu=['id'=>$comment['id'],'nome'=>$comment['nome'],'nome_social'=>$comment['nome_social'],'foto_url'=>$comment['foto_url']];
                ?>
                    <div class="comment"><div><?=avatar($cu,true)?></div><div class="comment-body"><strong><?=e(display_name($cu))?></strong><span><?=nl2br(e($comment['conteudo']))?></span></div></div>
                <?php endforeach; ?>

                <form class="comment-form" method="post">
                    <?=csrf_field()?>
                    <input type="hidden" name="action" value="comentar">
                    <input type="hidden" name="publicacao_id" value="<?=$post['id']?>">
                    <input name="comentario" placeholder="Escreva um comentário..." required>
                    <button class="btn sm" type="submit">Enviar</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</section>
</div>
<?php endif; ?>

<?php require __DIR__.'/includes/footer.php'; ?>
