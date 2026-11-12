<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
$admin=require_admin();

$id=(int)($_GET['id']??0);
$s=$pdo->prepare("SELECT d.*, dn.nome AS denunciante_nome, dn.nome_social AS denunciante_social, dn.email AS denunciante_email,
    dd.nome AS denunciado_nome, dd.nome_social AS denunciado_social, dd.email AS denunciado_email, dd.ativo AS denunciado_ativo, dd.nivel_acesso AS denunciado_nivel,
    p.conteudo AS post_conteudo, p.data_criacao AS post_data
    FROM denuncias d
    JOIN usuarios dn ON dn.id=d.denunciante_id
    JOIN usuarios dd ON dd.id=d.denunciado_id
    LEFT JOIN publicacoes p ON p.id=d.publicacao_id
    WHERE d.id=?");
$s->execute([$id]);
$d=$s->fetch();
if(!$d){flash('Denúncia não encontrada.','error');redirect('denuncias.php');}

// Mensagem única apontada pela denúncia (se houver)
$msgUnica=null;
if($d['mensagem_id']){$q=$pdo->prepare('SELECT conteudo,data_envio,remetente_id FROM mensagens WHERE id=?');$q->execute([(int)$d['mensagem_id']]);$msgUnica=$q->fetch()?:null;}

// Prova anexada por quem denunciou: o ADM só enxerga ESTE recorte, nunca as conversas em geral.
$evid=[];$evidErro=false;
try{
    $q=$pdo->prepare('SELECT e.*,u.nome,u.nome_social FROM denuncia_evidencias e JOIN usuarios u ON u.id=e.remetente_id WHERE e.denuncia_id=? ORDER BY e.data_envio,e.id');
    $q->execute([$id]);$evid=$q->fetchAll();
}catch(PDOException $e){$evidErro=true;}

$q=$pdo->prepare("SELECT COUNT(*) FROM denuncias WHERE denunciado_id=? AND id<>?");$q->execute([(int)$d['denunciado_id'],$id]);$outras=(int)$q->fetchColumn();

admin_log('visualizou denúncia','denuncia',$id,'prova anexada: '.count($evid).' mensagem(ns)');
$nome=fn($n,$soc)=>($soc?:$n);
$flash=get_flash();
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Denúncia #<?=$id?> — Lindr</title><link rel="stylesheet" href="css/admin.css"></head><body>
<?php include __DIR__.'/partials.php'; ?>
<main class="admin-main">
<p><a href="denuncias.php">← Voltar às denúncias</a></p>
<h1>Denúncia #<?=$id?></h1>
<?php if($flash):?><div class="flash <?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif;?>

<section class="card"><h2>Resumo</h2>
<div class="table-wrap"><table><tbody>
<tr><th>Data</th><td><?=e(date('d/m/Y H:i',strtotime($d['data_denuncia'])))?></td></tr>
<tr><th>Motivo</th><td><?=e($d['motivo'])?></td></tr>
<tr><th>Status</th><td><?=e($d['status'])?></td></tr>
<tr><th>Denunciante</th><td><?=e($nome($d['denunciante_nome'],$d['denunciante_social']))?> <span class="muted">(#<?=$d['denunciante_id']?>)</span></td></tr>
<tr><th>Denunciado</th><td><?=e($nome($d['denunciado_nome'],$d['denunciado_social']))?> <span class="muted">(#<?=$d['denunciado_id']?><?=(int)$d['denunciado_ativo']!==1?' · conta suspensa':''?>)</span><?php if($outras):?> <span class="muted">· <?=$outras?> outra(s) denúncia(s) contra esta conta</span><?php endif;?></td></tr>
<tr><th>Relato</th><td><?=nl2br(e($d['descricao']?:'— sem descrição —'))?></td></tr>
</tbody></table></div></section>

<?php if($d['publicacao_id']):?>
<section class="card"><h2>Publicação denunciada</h2>
<?php if($d['post_conteudo']!==null):?><p class="muted">Publicada em <?=e(date('d/m/Y H:i',strtotime($d['post_data'])))?></p><p style="white-space:pre-wrap;overflow-wrap:anywhere"><?=e($d['post_conteudo'])?></p>
<?php else:?><p class="muted">A publicação já foi removida.</p><?php endif;?></section>
<?php endif;?>

<section class="card"><h2>Prova da conversa</h2>
<p class="muted">🔒 Aqui aparece <b>somente</b> o recorte que a pessoa denunciante escolheu anexar. A administração não tem acesso às demais conversas.</p>
<?php if($evidErro):?><p class="muted">Tabela de provas ausente. Rode <code>evidencias_migration.sql</code> no banco.</p>
<?php elseif($evid):?>
<div class="evid"><?php foreach($evid as $m):$denunciante=((int)$m['remetente_id']===(int)$d['denunciante_id']);?>
<div class="evid-msg <?=$denunciante?'a':'b'?>"><small><b><?=e($nome($m['nome'],$m['nome_social']))?></b> · <?=e(date('d/m/Y H:i',strtotime($m['data_envio'])))?><?=$denunciante?' (denunciante)':' (denunciado)'?></small><div><?=e($m['conteudo'])?></div></div>
<?php endforeach;?></div>
<?php elseif($msgUnica):?>
<div class="evid"><div class="evid-msg b"><small><?=e(date('d/m/Y H:i',strtotime($msgUnica['data_envio'])))?></small><div><?=e($msgUnica['conteudo'])?></div></div></div>
<?php else:?><p class="muted">Nenhuma conversa foi anexada a esta denúncia.</p><?php endif;?>
</section>

<section class="card"><h2>Decisão</h2>
<div class="actions-row">
<form method="post" action="denuncias.php" class="inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><select name="status"><?php foreach(['aberta','em_analise','resolvida','arquivada'] as $x):?><option value="<?=$x?>" <?=$d['status']===$x?'selected':''?>><?=$x?></option><?php endforeach;?></select><button class="btn sm">Salvar status</button></form>
<?php if($d['publicacao_id'] && $d['post_conteudo']!==null):?><form method="post" action="denuncias.php" class="inline" onsubmit="return confirm('Remover esta publicação?');"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="acao" value="remover_post"><input type="hidden" name="publicacao_id" value="<?=$d['publicacao_id']?>"><button class="btn sm danger">Remover post</button></form><?php endif;?>
<?php if((int)$d['denunciado_ativo']===1 && $d['denunciado_nivel']!=='admin'):?><form method="post" action="denuncias.php" class="inline" onsubmit="return confirm('Suspender esta conta?');"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="acao" value="suspender"><input type="hidden" name="usuario_id" value="<?=$d['denunciado_id']?>"><button class="btn sm danger">Suspender usuário</button></form><?php endif;?>
</div></section>
</main></body></html>
