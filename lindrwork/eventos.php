<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/app_helpers.php';
require_once __DIR__ . '/includes/locais.php';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($a === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        $date = $_POST['event_date'] ?? '';
        $name = mb_substr($name, 0, 150);
        $desc = mb_substr($desc, 0, 2000);
        $loc = mb_substr($loc, 0, 255);
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $date);
        // estado e cidade do evento são escolhidos da lista
        [$evUf, $evCity, $locErr] = location_from_post('state', 'city', true);
        if ($name && $date && !$dt) flash('Data e hora inválidas.', 'error');
        elseif ($name && $date && $dt <= new DateTime()) flash('A data do evento precisa ser no futuro.', 'error');
        elseif ($locErr) flash($locErr, 'error');
        elseif ($name && $date) {
            $s = $pdo->prepare('INSERT INTO grupos(criador_id,tipo,nome,descricao,local_evento,cidade,estado,data_evento) VALUES(?,"evento",?,?,?,?,?,?)');
            $s->execute([$me['id'], $name, $desc, $loc, $evCity, $evUf, str_replace('T', ' ', $date)]);
            $eid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO grupo_membros(grupo_id,usuario_id,papel) VALUES(?,?,"admin")')
                ->execute([$eid, $me['id']]);
            flash('Evento criado.');
        }
    } elseif ($a === 'join') {
        $ev0 = $pdo->prepare('SELECT 1 FROM grupos WHERE id=? AND tipo="evento" AND status="ativo"');
        $ev0->execute([$id]);
        if (!$ev0->fetchColumn()) {
            flash('Evento não encontrado ou cancelado.', 'error');
            redirect('eventos.php');
        }
        $q = $pdo->prepare('SELECT 1 FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');
        $q->execute([$id, $me['id']]);
        if ($q->fetchColumn()) $pdo->prepare('DELETE FROM grupo_membros WHERE grupo_id=? AND usuario_id=?')
            ->execute([$id, $me['id']]);
        else $pdo->prepare('INSERT IGNORE INTO grupo_membros(grupo_id,usuario_id) VALUES(?,?)')
            ->execute([$id, $me['id']]);
    } elseif ($a === 'cancel') $pdo->prepare('UPDATE grupos SET status="cancelado" WHERE id=? AND criador_id=? AND tipo="evento"')->execute([$id, $me['id']]);
    redirect('eventos.php');
}
$ev = $pdo->query('SELECT g.*,COUNT(gm.usuario_id) participants FROM grupos g LEFT JOIN grupo_membros gm ON gm.grupo_id=g.id WHERE g.tipo="evento" GROUP BY g.id ORDER BY (g.status="cancelado"), (g.data_evento < NOW()), CASE WHEN g.data_evento >= NOW() THEN g.data_evento END ASC, g.data_evento DESC')
    ->fetchAll();
$page = 'eventos';
$title = 'Eventos — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap">
        <div class="row" style="justify-content:space-between">
            <h2>Eventos</h2>
            <button class="btn sm" onclick="document.getElementById('newEvent').showModal()">Criar evento</button>
        </div>
        <div class="grid" style="margin-top:18px">
            <?php
            foreach ($ev as $e):
                $q = $pdo->prepare('SELECT 1 FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');
                $q->execute([$e['id'], $me['id']]);
                $joined = (bool)$q->fetchColumn();
                $past = strtotime((string)$e['data_evento']) < time();
                ?>
                <div class="card" style="<?=($e['status']==='cancelado'||$past)?'opacity:.6':''?>">
                    <h3><a style="color:inherit;text-decoration:none" href="grupo.php?id=<?=$e['id']?>"><?= e($e['nome']) ?></a></h3>
                    <p class="mut"><?= icon('cal', 14) ?> <?= e(date('d/m/Y H:i', strtotime($e['data_evento']))) ?><br><?= icon('pin', 14) ?> <?= e(event_place($e)) ?></p>
                    <p><?= e($e['descricao']) ?></p>
                    <p class="mut"><?= $e['participants'] ?> participantes<?= $e['status'] === 'cancelado'?' · cancelado':($past?' · encerrado':'') ?></p>
                    <p><a href="grupo.php?id=<?=$e['id']?>">Ver detalhes e comentários →</a></p>
                    <?php if ($e['status'] === 'ativo' && !$past): ?>
                        <div class="row">
                            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="join"><input type="hidden" name="id" value="<?=$e['id']?>"><button class="btn sm <?=$joined?'ghost':''?>"><?= $joined?'Sair':'Participar' ?></button></form>
                            <?php if ($e['criador_id'] == $me['id']): ?>
                                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?=$e['id']?>"><button class="btn sm ghost">Cancelar evento</button></form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<dialog id="newEvent" class="card" style="border:0;max-width:420px;width:90%">
    <form method="post"><input type="hidden" name="action" value="create"><?= csrf_field() ?>
        <h3>Novo evento</h3>
        <label>Nome</label>
        <input name="name" required>
        <label>Descrição</label>
        <textarea name="description" rows="2"></textarea>
        <?= location_fields() ?>
        <label>Endereço ou ponto de encontro</label>
        <input name="location" required maxlength="255" placeholder="Ex.: Praça central, em frente à fonte">
        <label>Data e hora</label>
        <input name="event_date" type="datetime-local" required min="<?=date('Y-m-d\TH:i')?>">
        <div class="acts"><button class="btn ghost" type="button" onclick="this.closest('dialog').close()">Cancelar</button><button class="btn">Criar</button></div>
    </form>
</dialog>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require __DIR__.'/config/database.php';require __DIR__.'/includes/app_helpers.php';$me=require_login();
if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();$a=$_POST['action']??'';$id=(int)($_POST['id']??0);
    if($a==='create'){
        $name=trim($_POST['name']??'');$desc=trim($_POST['description']??'');$loc=trim($_POST['location']??'');$date=$_POST['event_date']??'';
        $name=mb_substr($name,0,150);$desc=mb_substr($desc,0,2000);$loc=mb_substr($loc,0,255);
        $dt=DateTime::createFromFormat('Y-m-d\TH:i',$date);
        if($name&&$date&&!$dt)flash('Data e hora inválidas.','error');
        elseif($name&&$date){$s=$pdo->prepare('INSERT INTO grupos(criador_id,tipo,nome,descricao,local_evento,data_evento) VALUES(?,"evento",?,?,?,?)');$s->execute([$me['id'],$name,$desc,$loc,str_replace('T',' ',$date)]);$eid=$pdo->lastInsertId();$pdo->prepare('INSERT INTO grupo_membros(grupo_id,usuario_id,papel) VALUES(?,?,"admin")')->execute([$eid,$me['id']]);flash('Evento criado.');}
    }elseif($a==='join'){
        $ev0=$pdo->prepare('SELECT 1 FROM grupos WHERE id=? AND tipo="evento" AND status="ativo"');$ev0->execute([$id]);
        if(!$ev0->fetchColumn()){flash('Evento não encontrado ou cancelado.','error');redirect('eventos.php');}
        $q=$pdo->prepare('SELECT 1 FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');$q->execute([$id,$me['id']]);
        if($q->fetchColumn())$pdo->prepare('DELETE FROM grupo_membros WHERE grupo_id=? AND usuario_id=?')->execute([$id,$me['id']]);else $pdo->prepare('INSERT IGNORE INTO grupo_membros(grupo_id,usuario_id) VALUES(?,?)')->execute([$id,$me['id']]);
    }elseif($a==='cancel')$pdo->prepare('UPDATE grupos SET status="cancelado" WHERE id=? AND criador_id=? AND tipo="evento"')->execute([$id,$me['id']]);
    redirect('eventos.php');
}
$ev=$pdo->query('SELECT g.*,COUNT(gm.usuario_id) participants FROM grupos g LEFT JOIN grupo_membros gm ON gm.grupo_id=g.id WHERE g.tipo="evento" GROUP BY g.id ORDER BY (g.status="cancelado"), (g.data_evento < NOW()), CASE WHEN g.data_evento >= NOW() THEN g.data_evento END ASC, g.data_evento DESC')->fetchAll();
$page='eventos';$title='Eventos — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php';
?>
<div class="pg"><main class="wrap"><div class="row" style="justify-content:space-between"><h2>Eventos</h2><button class="btn sm" onclick="document.getElementById('newEvent').showModal()">Criar evento</button></div><div class="grid" style="margin-top:18px"><?php foreach($ev as $e):$q=$pdo->prepare('SELECT 1 FROM grupo_membros WHERE grupo_id=? AND usuario_id=?');$q->execute([$e['id'],$me['id']]);$joined=(bool)$q->fetchColumn();$past=strtotime((string)$e['data_evento'])<time(); ?><div class="card" style="<?=($e['status']==='cancelado'||$past)?'opacity:.6':''?>"><h3><a style="color:inherit;text-decoration:none" href="grupo.php?id=<?=$e['id']?>"><?=e($e['nome'])?></a></h3><p class="mut"><?=icon('cal',14)?> <?=e(date('d/m/Y H:i',strtotime($e['data_evento'])))?><br><?=icon('pin',14)?> <?=e($e['local_evento'])?></p><p><?=e($e['descricao'])?></p><p class="mut"><?=$e['participants']?> participantes<?=$e['status']==='cancelado'?' · cancelado':($past?' · encerrado':'')?></p><p><a href="grupo.php?id=<?=$e['id']?>">Ver detalhes e comentários →</a></p><?php if($e['status']==='ativo' && !$past): ?><div class="row"><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="join"><input type="hidden" name="id" value="<?=$e['id']?>"><button class="btn sm <?=$joined?'ghost':''?>"><?=$joined?'Sair':'Participar'?></button></form><?php if($e['criador_id']==$me['id']): ?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?=$e['id']?>"><button class="btn sm ghost">Cancelar evento</button></form><?php endif; ?></div><?php endif; ?></div><?php endforeach; ?></div></main></div><dialog id="newEvent" class="card" style="border:0;max-width:420px;width:90%"><form method="post"><input type="hidden" name="action" value="create"><?=csrf_field()?><h3>Novo evento</h3><label>Nome</label><input name="name" required><label>Descrição</label><textarea name="description" rows="2"></textarea><label>Local</label><input name="location" required><label>Data e hora</label><input name="event_date" type="datetime-local" required><div class="acts"><button class="btn ghost" type="button" onclick="this.closest('dialog').close()">Cancelar</button><button class="btn">Criar</button></div></form></dialog><?php require __DIR__.'/includes/footer.php'; ?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
