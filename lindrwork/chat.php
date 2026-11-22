<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/app_helpers.php';
$me = require_login();
$with = (int)($_GET['with'] ?? $_POST['with'] ?? 0);

// ---- Pessoa com quem a conversa está aberta (opcional: o chat abre mesmo sem conversas) ----
$other = null;
$matchId = null;
if ($with > 0) {
    $q = $pdo->prepare('SELECT u.* FROM usuarios u WHERE u.id=? AND u.ativo=1 AND EXISTS (SELECT 1 FROM matches m WHERE m.status="match" AND ((m.usuario1_id=? AND m.usuario2_id=u.id) OR (m.usuario1_id=u.id AND m.usuario2_id=?)))');
    $q->execute([$with, $me['id'], $me['id']]);
    $other = $q->fetch()?:null;
    if (!$other || blocked_between((int)$me['id'], (int)$other['id'])) {
        flash('Essa conversa não está disponível.', 'error');
        redirect('chat.php');
    }
    $matchId = find_conversation((int)$me['id'], (int)$other['id']);
    if (!$matchId) redirect('chat.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!$other) redirect('chat.php');
    $text = trim($_POST['message'] ?? '');
    if ($text !== '') {
        $text = mb_substr($text, 0, 2000);
        $pdo->prepare('INSERT INTO mensagens(match_id,remetente_id,conteudo) VALUES(?,?,?)')
            ->execute([$matchId, $me['id'], $text]);
        // Uma notificação por conversa enquanto não for lida (evita uma notificação por mensagem).
        $dup = $pdo->prepare("SELECT 1 FROM notificacoes WHERE usuario_id=? AND ator_id=? AND tipo='mensagem' AND lida=0 LIMIT 1");
        $dup->execute([$other['id'], $me['id']]);
        if (!$dup->fetchColumn()) notify_user(
            (int)$other['id'],
            'mensagem',
            'Você recebeu uma mensagem',
            display_name($me) . ' enviou uma mensagem.',
            'chat.php?with=' . $me['id'],
            (int)$me['id']
        );
    }
    redirect('chat.php?with=' . $other['id']);
}

// ---- Lista de conversas (todas as amizades com match, com ou sem mensagens) ----
$contacts = [];
foreach (mutual_matches() as $r) {
    $q = $pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');
    $q->execute([$r['other_id']]);
    $p = $q->fetch();
    if (!$p || blocked_between((int)$me['id'], (int)$p['id'])) continue;
    $mid = find_conversation((int)$me['id'], (int)$p['id']);
    if (!$mid) continue;
    $l = $pdo->prepare('SELECT conteudo,data_envio FROM mensagens WHERE match_id=? ORDER BY data_envio DESC,id DESC LIMIT 1');
    $l->execute([$mid]);
    $last = $l->fetch()?:null;
    $n = $pdo->prepare('SELECT COUNT(*) FROM mensagens WHERE match_id=? AND remetente_id<>? AND lida=0');
    $n->execute([$mid, $me['id']]);
    $contacts[] = [
        'p' => $p,
        'last' => $last,
        'unread' => (int)$n->fetchColumn(),
        'sort' => $last?strtotime($last['data_envio']):strtotime($r['data_match'] ?? $r['data_criacao'] ?? 'now')
    ];
}
usort($contacts, fn($a, $b) => $b['sort'] <=> $a['sort']);

// ---- Mensagens da conversa aberta ----
$messages = [];
if ($other) {
    $q = $pdo->prepare('SELECT m.*,u.nome,u.nome_social FROM mensagens m JOIN usuarios u ON u.id=m.remetente_id WHERE m.match_id=? ORDER BY m.data_envio,m.id');
    $q->execute([$matchId]);
    $messages = $q->fetchAll();
    $pdo->prepare('UPDATE mensagens SET lida=1 WHERE match_id=? AND remetente_id<>?')
        ->execute([$matchId, $me['id']]);
    foreach ($contacts as &$c) {
        if ((int)$c['p']['id'] === (int)$other['id']) $c['unread'] = 0;
    }
    unset($c);
}

$page = 'chat';
$title = 'Chat — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap">
        <div class="chat chat-lista">
            <aside class="card" style="margin:0;align-self:start">
                <h3 style="margin:0 0 6px">Conversas</h3>
                <?php if (!$contacts): ?>
                    <p class="mut" style="margin:8px 0 0">Nenhuma conversa por aqui ainda.</p>
                    <?php
                else:
                    foreach ($contacts as $c):
                        $p = $c['p'];
                        $on = $other && (int)$p['id'] === (int)$other['id'];
                        ?>
                        <a class="li" href="chat.php?with=<?=$p['id']?>" style="text-decoration:none;color:var(--ink);<?=$on?'background:var(--chip);border-radius:12px;padding:10px;':''?>"> <?= avatar($p) ?>
                        <div style="flex:1;min-width:0"><b><?= e(display_name($p)) ?></b>
                            <div class="mut" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($c['last']?mb_strimwidth($c['last']['conteudo'], 0, 40, '…'):'Comece a conversa 👋') ?></div>
                        </div>
                        <?php if ($c['unread']): ?>
                            <b class="pill"><?= $c['unread'] > 9?'9+':$c['unread'] ?></b>
                        <?php endif; ?>
                        </a>
                        <?php
                    endforeach;
                endif;
                ?>
            </aside>
            <section class="card" style="margin:0">
                <?php if ($other): ?>
                    <div class="row" style="justify-content:space-between">
                        <div class="row"><?= avatar($other) ?><b><?= e(display_name($other)) ?></b></div>
                        <span class="row"><a class="btn ghost sm" href="denunciar.php?usuario_id=<?=$other['id']?>&amp;chat=1">Denunciar</a>
                        <form method="post" action="bloquear.php" onsubmit="return confirm('Bloquear esta pessoa? A conversa será encerrada.')"><?= csrf_field() ?><input type="hidden" name="target_id" value="<?=$other['id']?>"><button class="btn ghost sm" type="submit">Bloquear</button></form>
                        </span>
                    </div>
                    <div class="msgs" id="messages">
                        <?php if (!$messages): ?>
                            <p class="mut">Diga oi! 👋</p>
                            <?php
                        endif;
                        foreach ($messages as $m):
                            ?>
                            <div class="b <?=$m['remetente_id']==$me['id']?'me':''?>"><?= e($m['conteudo']) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="with" value="<?=$other['id']?>"><input name="message" required maxlength="2000" placeholder="Escreva uma mensagem" autocomplete="off" style="flex:1"><button class="btn" type="submit">Enviar</button></form>
                <?php elseif (!$contacts): ?>
                    <div class="empty">
                        <div style="font-size:42px">💬</div>
                        <h3 style="margin:8px 0">Você ainda não tem conversas</h3>
                        <p>Quando você der match com alguém, a conversa aparece aqui.</p>
                        <div class="actions" style="justify-content:center"><a class="btn" href="descobrir.php">Descobrir pessoas</a><a class="btn secondary" href="matches.php">Ver amizades</a></div>
                    </div>
                <?php else: ?>
                    <div class="empty">
                        <div style="font-size:42px">💬</div>
                        <h3 style="margin:8px 0">Escolha uma conversa</h3>
                        <p>Selecione alguém na lista ao lado para conversar.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require __DIR__.'/config/database.php';
require __DIR__.'/includes/app_helpers.php';
$me=require_login();
$with=(int)($_GET['with']??$_POST['with']??0);

// ---- Pessoa com quem a conversa está aberta (opcional: o chat abre mesmo sem conversas) ----
$other=null;$matchId=null;
if($with>0){
    $q=$pdo->prepare('SELECT u.* FROM usuarios u WHERE u.id=? AND u.ativo=1 AND EXISTS (SELECT 1 FROM matches m WHERE m.status="match" AND ((m.usuario1_id=? AND m.usuario2_id=u.id) OR (m.usuario1_id=u.id AND m.usuario2_id=?)))');
    $q->execute([$with,$me['id'],$me['id']]);
    $other=$q->fetch()?:null;
    if(!$other || blocked_between((int)$me['id'],(int)$other['id'])){
        flash('Essa conversa não está disponível.','error');
        redirect('chat.php');
    }
    $matchId=find_conversation((int)$me['id'],(int)$other['id']);
    if(!$matchId) redirect('chat.php');
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    if(!$other) redirect('chat.php');
    $text=trim($_POST['message']??'');
    if($text!==''){
        $text=mb_substr($text,0,2000);
        $pdo->prepare('INSERT INTO mensagens(match_id,remetente_id,conteudo) VALUES(?,?,?)')->execute([$matchId,$me['id'],$text]);
        // Uma notificação por conversa enquanto não for lida (evita uma notificação por mensagem).
        $dup=$pdo->prepare("SELECT 1 FROM notificacoes WHERE usuario_id=? AND ator_id=? AND tipo='mensagem' AND lida=0 LIMIT 1");
        $dup->execute([$other['id'],$me['id']]);
        if(!$dup->fetchColumn()) notify_user((int)$other['id'],'mensagem','Você recebeu uma mensagem',display_name($me).' enviou uma mensagem.','chat.php?with='.$me['id'],(int)$me['id']);
    }
    redirect('chat.php?with='.$other['id']);
}

// ---- Lista de conversas (todas as amizades com match, com ou sem mensagens) ----
$contacts=[];
foreach(mutual_matches() as $r){
    $q=$pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');$q->execute([$r['other_id']]);$p=$q->fetch();
    if(!$p || blocked_between((int)$me['id'],(int)$p['id'])) continue;
    $mid=find_conversation((int)$me['id'],(int)$p['id']);
    if(!$mid) continue;
    $l=$pdo->prepare('SELECT conteudo,data_envio FROM mensagens WHERE match_id=? ORDER BY data_envio DESC,id DESC LIMIT 1');$l->execute([$mid]);$last=$l->fetch()?:null;
    $n=$pdo->prepare('SELECT COUNT(*) FROM mensagens WHERE match_id=? AND remetente_id<>? AND lida=0');$n->execute([$mid,$me['id']]);
    $contacts[]=['p'=>$p,'last'=>$last,'unread'=>(int)$n->fetchColumn(),'sort'=>$last?strtotime($last['data_envio']):strtotime($r['data_match']??$r['data_criacao']??'now')];
}
usort($contacts,fn($a,$b)=>$b['sort']<=>$a['sort']);

// ---- Mensagens da conversa aberta ----
$messages=[];
if($other){
    $q=$pdo->prepare('SELECT m.*,u.nome,u.nome_social FROM mensagens m JOIN usuarios u ON u.id=m.remetente_id WHERE m.match_id=? ORDER BY m.data_envio,m.id');
    $q->execute([$matchId]);
    $messages=$q->fetchAll();
    $pdo->prepare('UPDATE mensagens SET lida=1 WHERE match_id=? AND remetente_id<>?')->execute([$matchId,$me['id']]);
    foreach($contacts as &$c){ if((int)$c['p']['id']===(int)$other['id']) $c['unread']=0; } unset($c);
}

$page='chat';$title='Chat — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php';
?>
<div class="pg"><main class="wrap"><div class="chat chat-lista">
<aside class="card" style="margin:0;align-self:start">
    <h3 style="margin:0 0 6px">Conversas</h3>
    <?php if(!$contacts): ?>
        <p class="mut" style="margin:8px 0 0">Nenhuma conversa por aqui ainda.</p>
    <?php else: foreach($contacts as $c): $p=$c['p']; $on=$other&&(int)$p['id']===(int)$other['id']; ?>
        <a class="li" href="chat.php?with=<?=$p['id']?>" style="text-decoration:none;color:var(--ink);<?=$on?'background:var(--chip);border-radius:12px;padding:10px;':''?>">
            <?=avatar($p)?>
            <div style="flex:1;min-width:0"><b><?=e(display_name($p))?></b>
                <div class="mut" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=e($c['last']?mb_strimwidth($c['last']['conteudo'],0,40,'…'):'Comece a conversa 👋')?></div></div>
            <?php if($c['unread']): ?><b class="pill"><?=$c['unread']>9?'9+':$c['unread']?></b><?php endif; ?>
        </a>
    <?php endforeach; endif; ?>
</aside>
<section class="card" style="margin:0">
<?php if($other): ?>
    <div class="row" style="justify-content:space-between"><div class="row"><?=avatar($other)?><b><?=e(display_name($other))?></b></div><span class="row"><a class="btn ghost sm" href="denunciar.php?usuario_id=<?=$other['id']?>&amp;chat=1">Denunciar</a><form method="post" action="bloquear.php" onsubmit="return confirm('Bloquear esta pessoa? A conversa será encerrada.')"><?=csrf_field()?><input type="hidden" name="target_id" value="<?=$other['id']?>"><button class="btn ghost sm" type="submit">Bloquear</button></form></span></div>
    <div class="msgs" id="messages"><?php if(!$messages): ?><p class="mut">Diga oi! 👋</p><?php endif;foreach($messages as $m): ?><div class="b <?=$m['remetente_id']==$me['id']?'me':''?>"><?=e($m['conteudo'])?></div><?php endforeach; ?></div>
    <form method="post" class="row"><?=csrf_field()?><input type="hidden" name="with" value="<?=$other['id']?>"><input name="message" required maxlength="2000" placeholder="Escreva uma mensagem" autocomplete="off" style="flex:1"><button class="btn" type="submit">Enviar</button></form>
<?php elseif(!$contacts): ?>
    <div class="empty"><div style="font-size:42px">💬</div><h3 style="margin:8px 0">Você ainda não tem conversas</h3><p>Quando você der match com alguém, a conversa aparece aqui.</p><div class="actions" style="justify-content:center"><a class="btn" href="descobrir.php">Descobrir pessoas</a><a class="btn secondary" href="matches.php">Ver amizades</a></div></div>
<?php else: ?>
    <div class="empty"><div style="font-size:42px">💬</div><h3 style="margin:8px 0">Escolha uma conversa</h3><p>Selecione alguém na lista ao lado para conversar.</p></div>
<?php endif; ?>
</section>
</div></main></div><?php require __DIR__.'/includes/footer.php'; ?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
