<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
$me=require_login();
ob_start();

$q=trim($_GET['q']??''); $type=$_GET['tipo']??'todos'; $people=[];$posts=[];$groups=[];
if($q!==''){
 $like='%'.addcslashes($q,'%_\\').'%';
 if(in_array($type,['todos','pessoas'],true)){$s=$pdo->prepare("SELECT id,nome,nome_social,foto_url,bio,cidade,estado,pronomes FROM usuarios WHERE ativo=1 AND id<>? AND discover_enabled=1 AND (nome LIKE ? OR nome_social LIKE ? OR bio LIKE ? OR cidade LIKE ?) AND id NOT IN (SELECT bloqueado_id FROM bloqueios WHERE usuario_id=?) AND id NOT IN (SELECT usuario_id FROM bloqueios WHERE bloqueado_id=?) ORDER BY nome LIMIT 30");$s->execute([$me['id'],$like,$like,$like,$like,$me['id'],$me['id']]);$people=$s->fetchAll();}
 if(in_array($type,['todos','publicacoes'],true)){$s=$pdo->prepare("SELECT p.*,u.nome,u.nome_social,u.foto_url FROM publicacoes p JOIN usuarios u ON u.id=p.usuario_id WHERE u.ativo=1 AND (p.conteudo LIKE ? OR p.tema LIKE ?) AND p.usuario_id NOT IN (SELECT bloqueado_id FROM bloqueios WHERE usuario_id=?) AND p.usuario_id NOT IN (SELECT usuario_id FROM bloqueios WHERE bloqueado_id=?) ORDER BY p.data_publicacao DESC LIMIT 30");$s->execute([$like,$like,$me['id'],$me['id']]);$posts=$s->fetchAll();}
 if(in_array($type,['todos','comunidades'],true)){$s=$pdo->prepare("SELECT g.*,u.nome AS criador,(SELECT COUNT(*) FROM grupo_membros gm WHERE gm.grupo_id=g.id) membros FROM grupos g JOIN usuarios u ON u.id=g.criador_id WHERE g.status='ativo' AND g.tipo='comunidade' AND (g.nome LIKE ? OR g.descricao LIKE ? OR g.tema LIKE ?) AND (g.privada=0 OR EXISTS (SELECT 1 FROM grupo_membros mine WHERE mine.grupo_id=g.id AND mine.usuario_id=?)) ORDER BY membros DESC LIMIT 20");$s->execute([$like,$like,$like,$me['id']]);$groups=$s->fetchAll();}
}
?>
<h1>Buscar no Lindr</h1><p class="mut">Encontre pessoas, publicações e comunidades por nome, assunto ou interesse.</p>
<form class="card" method="get"><div class="field-row"><div><label for="q">O que você procura?</label><input id="q" name="q" value="<?=e($q)?>" placeholder="Ex.: música, jogos, Florianópolis" required></div><div><label for="tipo">Categoria</label><select id="tipo" name="tipo"><?php foreach(['todos'=>'Tudo','pessoas'=>'Pessoas','publicacoes'=>'Publicações','comunidades'=>'Comunidades'] as $k=>$v):?><option value="<?=$k?>" <?=$type===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div></div><div class="actions"><button class="btn">Buscar</button></div></form>
<?php if($q===''):?><div class="card empty">Digite um termo para começar a buscar.</div><?php else:?>
<h2>Pessoas <span class="pill"><?=count($people)?></span></h2><section class="card"><?php if(!$people):?><p class="empty">Nenhuma pessoa encontrada.</p><?php else:foreach($people as $u):?><div class="result"><div><?=avatar($u)?></div><div class="result-main"><b><?=e(display_name($u))?></b><p><?=e(trim(($u['cidade']??'').(!empty($u['estado'])?', '.$u['estado']:'')))?> <?=e($u['pronomes']??'')?></p><p><?=e(mb_strimwidth($u['bio']??'',0,180,'…'))?></p></div><a class="btn secondary" href="perfil_publico.php?id=<?=$u['id']?>">Ver perfil</a></div><?php endforeach;endif;?></section>
<h2>Publicações <span class="pill"><?=count($posts)?></span></h2><section class="card"><?php if(!$posts):?><p class="empty">Nenhuma publicação encontrada.</p><?php else:foreach($posts as $post):?><article class="feed-post"><div class="person"><?=avatar(['id'=>$post['usuario_id'],'nome'=>$post['nome'],'nome_social'=>$post['nome_social'],'foto_url'=>$post['foto_url']])?><div><b><?=e($post['nome_social']?:$post['nome'])?></b><div class="mut"><?=e($post['data_publicacao'])?></div></div></div><p class="post-content"><?=e($post['conteudo'])?></p><?php if($post['imagem_url']):?><img class="thumb" src="<?=e($post['imagem_url'])?>" alt="Imagem da publicação"><?php endif;?></article><?php endforeach;endif;?></section>
<h2>Comunidades <span class="pill"><?=count($groups)?></span></h2><section class="card"><?php if(!$groups):?><p class="empty">Nenhuma comunidade encontrada.</p><?php else:foreach($groups as $g):?><div class="result"><div class="result-main"><b><?=e($g['nome'])?></b><p><?=e(mb_strimwidth($g['descricao']??'',0,200,'…'))?></p><span class="pill"><?= (int)$g['membros']?> membros</span></div><a class="btn secondary" href="grupo.php?id=<?=$g['id']?>">Abrir</a></div><?php endforeach;endif;?></section><?php endif;?>

<?php
$content=ob_get_clean();
$page='buscar'; $title='Buscar — Lindr'; require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap">
<?= $content ?>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>