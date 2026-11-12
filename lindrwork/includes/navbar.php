<?php $me=user();
$unread=0;
if($me){ try{ $uq=$pdo->prepare('SELECT COUNT(*) FROM notificacoes WHERE usuario_id=? AND lida=0'); $uq->execute([$me['id']]); $unread=(int)$uq->fetchColumn(); }catch(Throwable $e){} }
$appPages=['index'=>'Feed','buscar'=>'Buscar','descobrir'=>'Pessoas','matches'=>'Amizades','chat'=>'Chat','comunidades'=>'Comunidades','eventos'=>'Eventos','notificacoes'=>'Avisos','perfil'=>'Perfil'];
$icons=['index'=>'home','buscar'=>'search','descobrir'=>'spark','matches'=>'heart','chat'=>'msg','comunidades'=>'users','eventos'=>'cal','notificacoes'=>'bell','perfil'=>'user']; ?>
<nav class="top">
    <div class="logo <?=!$me?'big':''?>" onclick="location.href='index.php'">Lindr<?php if(!$me): ?><span aria-hidden="true" style="font-size:22px">🏳️‍🌈</span><?php endif; ?></div>
    <?php if($me): ?>
        <div class="nl">
            <?php foreach($appPages as $item=>$label): ?><a class="<?= $page===$item?'on':'' ?>" href="<?=$item?>.php" title="<?=$label?>" aria-label="<?=$label?>"><?=icon($icons[$item],22)?><span><?=$label?></span><?php if($item==='notificacoes' && $unread): ?><b class="dot"><?=$unread>9?'9+':$unread?></b><?php endif; ?></a><?php endforeach; ?>
        </div>
        <div class="row"><span class="un"><?=e(display_name($me))?></span><?php if(is_admin()): ?><a class="btn dk sm" href="admin/index.php">Painel ADM</a><?php endif; ?><a class="btn dk sm" href="configuracoes.php">Configurações</a><form method="post" action="logout.php" style="display:inline"><?=csrf_field()?><button class="btn dk sm" type="submit">Sair</button></form></div>
    <?php else: ?>
        <div class="row"><a href="index.php#rec">Recursos</a><a class="btn dk" href="login.php">Entrar</a></div>
    <?php endif; ?>
</nav>
<?php if($flash): ?><div class="toast" style="display:block" id="flash"><?=e($flash['message'])?></div><script>setTimeout(()=>{const x=document.getElementById('flash');if(x)x.style.display='none'},3000)</script><?php endif; ?>
