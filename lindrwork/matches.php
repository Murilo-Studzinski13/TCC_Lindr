<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/app_helpers.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id=(int)($_POST['match_id']??0);
    $action=$_POST['action']??'';
    $s=$pdo->prepare('SELECT * FROM matches WHERE id=? AND usuario2_id=? AND status="pendente" LIMIT 1');
    $s->execute([$id,$me['id']]);
    $r=$s->fetch();
    if($r && blocked_between((int)$me['id'],(int)$r['usuario1_id'])){
        flash('Não é possível responder a este pedido.','error');
        redirect('matches.php');
    }
    if($r){
        if($action==='accept'){
            $pdo->prepare('UPDATE matches SET status="match",data_match=NOW() WHERE id=?')->execute([$id]);
            // Cria (ou atualiza) a linha no sentido inverso, para a pessoa não reaparecer em Descobrir.
            $pdo->prepare('INSERT INTO matches(usuario1_id,usuario2_id,status,data_match) VALUES(?,?,"match",NOW()) ON DUPLICATE KEY UPDATE status="match",data_match=NOW()')
                ->execute([$me['id'],$r['usuario1_id']]);
            notify_user((int)$r['usuario1_id'],'match','Seu pedido foi aceito','Vocês deram match e já podem conversar.','matches.php',(int)$me['id']);
            flash('Match feito!');
        }else if($action==='reject'){
            $pdo->prepare('UPDATE matches SET status="rejeitado" WHERE id=?')->execute([$id]);
            flash('Pedido recusado.');
        }
    }
    redirect('matches.php');
}

// Atenção: m.* e u.* têm a coluna "id" nas duas tabelas; por isso o id do match vai com alias.
$incoming=$pdo->prepare('SELECT u.*, m.id AS match_id FROM matches m JOIN usuarios u ON u.id=m.usuario1_id WHERE m.usuario2_id=? AND m.status="pendente" AND u.ativo=1 AND NOT EXISTS (SELECT 1 FROM bloqueios b WHERE (b.usuario_id=u.id AND b.bloqueado_id=?) OR (b.usuario_id=? AND b.bloqueado_id=u.id)) ORDER BY m.data_criacao DESC');
$incoming->execute([$me['id'],$me['id'],$me['id']]);
$incoming=$incoming->fetchAll();
$matches=mutual_matches();

$page='matches';$title='Amizades — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php';
?>
<div class="pg"><main class="wrap"><h2>Amizades</h2><h3 style="margin:18px 0 12px">Pedidos recebidos</h3>
<div class="card"><?php if(!$incoming): ?><p class="mut">Ninguém esperando resposta por agora.</p><?php else: foreach($incoming as $r): ?><div class="li"><?=avatar($r)?><div style="flex:1"><b><?=e(display_name($r))?></b><div class="mut"><?=e(location_text($r))?></div></div><form method="post" class="row"><?=csrf_field()?><input type="hidden" name="match_id" value="<?=$r['match_id']?>"><button class="btn sm" name="action" value="accept">Aceitar</button><button class="btn sm ghost" name="action" value="reject">Recusar</button></form></div><?php endforeach; endif; ?></div>
<h3 id="meus" style="margin:26px 0 12px">Seus matches</h3><div class="card"><?php if(!$matches): ?><p class="mut">Sem matches ainda. Curta perfis em Descobrir.</p><?php else: foreach($matches as $r): $q=$pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');$q->execute([$r['other_id']]);$p=$q->fetch();if(!$p)continue; ?><div class="li"><?=avatar($p)?><div style="flex:1"><b><?=e(display_name($p))?></b><div class="mut"><?=e(location_text($p))?></div></div><a class="btn sm" href="chat.php?with=<?=$p['id']?>">Conversar</a></div><?php endforeach;endif; ?></div>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>
