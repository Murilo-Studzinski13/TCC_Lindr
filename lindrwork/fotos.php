<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
$me=require_login();
ob_start();

const MAX_FOTOS=8;
// De onde o formulário foi enviado (o perfil também tem o botão de adicionar foto).
$back=(($_POST['voltar']??$_GET['voltar']??'')==='perfil')?'perfil.php#fotos':'fotos.php';

/** Sincroniza a foto principal do perfil (usuarios.foto_url) com a galeria. */
function sync_main_photo(PDO $pdo,int $uid):void{
    $s=$pdo->prepare('SELECT foto_url FROM fotos WHERE usuario_id=? ORDER BY principal DESC,ordem,id LIMIT 1');
    $s->execute([$uid]);$url=$s->fetchColumn();
    $pdo->prepare('UPDATE usuarios SET foto_url=? WHERE id=?')->execute([$url?:null,$uid]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $action=$_POST['action']??'';

    if($action==='add'||$action==='upload'){
        $c=$pdo->prepare('SELECT COUNT(*) FROM fotos WHERE usuario_id=?');$c->execute([$me['id']]);$count=(int)$c->fetchColumn();
        if($count>=MAX_FOTOS){flash('Você pode manter até '.MAX_FOTOS.' fotos na galeria.','error');redirect($back);}

        if($action==='upload'){
            [$url,$err]=save_uploaded_photo($_FILES['arquivo']??null);
            if($err){flash($err,'error');redirect($back);}
        }else{
            $url=trim($_POST['foto_url']??'');
            if(!valid_image_url($url)){flash('Informe uma URL válida de imagem.','error');redirect($back);}
        }

        $first=($count===0);
        $pdo->prepare('INSERT INTO fotos(usuario_id,foto_url,principal,ordem) VALUES(?,?,?,?)')->execute([$me['id'],$url,$first?1:0,$count]);
        // A primeira foto (ou quem ainda não tem foto de perfil) já vira a foto principal.
        if($first||empty($me['foto_url'])) $pdo->prepare('UPDATE usuarios SET foto_url=? WHERE id=?')->execute([$url,$me['id']]);
        if($first) $pdo->prepare('UPDATE fotos SET principal=(foto_url=?) WHERE usuario_id=?')->execute([$url,$me['id']]);
        flash('Foto adicionada à galeria.');
    }
    elseif($action==='principal'&&ctype_digit($_POST['id']??'')){
        $f=$pdo->prepare('SELECT foto_url FROM fotos WHERE id=? AND usuario_id=?');$f->execute([(int)$_POST['id'],$me['id']]);$url=$f->fetchColumn();
        if($url){
            $pdo->prepare('UPDATE fotos SET principal=(id=?) WHERE usuario_id=?')->execute([(int)$_POST['id'],$me['id']]);
            $pdo->prepare('UPDATE usuarios SET foto_url=? WHERE id=?')->execute([$url,$me['id']]);
            flash('Foto principal atualizada.');
        }
    }
    elseif($action==='delete'&&ctype_digit($_POST['id']??'')){
        $f=$pdo->prepare('SELECT foto_url,principal FROM fotos WHERE id=? AND usuario_id=?');$f->execute([(int)$_POST['id'],$me['id']]);$row=$f->fetch();
        if($row){
            $pdo->prepare('DELETE FROM fotos WHERE id=? AND usuario_id=?')->execute([(int)$_POST['id'],$me['id']]);
            delete_photo_file($row['foto_url']);
            if($row['principal']||$row['foto_url']===($me['foto_url']??null)){
                // a principal saiu: a próxima foto assume (ou fica sem foto)
                $pdo->prepare('UPDATE fotos SET principal=0 WHERE usuario_id=?')->execute([$me['id']]);
                $n=$pdo->prepare('SELECT id FROM fotos WHERE usuario_id=? ORDER BY ordem,id LIMIT 1');$n->execute([$me['id']]);$nid=$n->fetchColumn();
                if($nid)$pdo->prepare('UPDATE fotos SET principal=1 WHERE id=?')->execute([$nid]);
                sync_main_photo($pdo,(int)$me['id']);
            }
            flash('Foto removida.');
        }
    }
    redirect($back);
}

$s=$pdo->prepare('SELECT * FROM fotos WHERE usuario_id=? ORDER BY principal DESC,ordem,id');$s->execute([$me['id']]);$photos=$s->fetchAll();
?>
<h1>Galeria de fotos</h1>
<p class="mut">Adicione até <?=MAX_FOTOS?> fotos ao seu perfil. Use apenas imagens suas ou que você tenha autorização para publicar.</p>

<form class="card" method="post" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="action" value="upload">
    <label for="arquivo">Enviar foto do seu aparelho</label>
    <input id="arquivo" type="file" name="arquivo" accept="image/jpeg,image/png,image/webp" required>
    <p class="mut" style="margin:6px 0 0">JPG, PNG ou WEBP, até 5 MB.</p>
    <div class="actions"><button class="btn" type="submit">Enviar foto</button></div>
</form>

<details class="card"><summary style="cursor:pointer;font-weight:700">Ou adicionar por link (URL)</summary>
<form method="post"><?=csrf_field()?><input type="hidden" name="action" value="add">
    <label>URL da imagem</label><input type="url" name="foto_url" placeholder="https://..." required>
    <div class="actions"><button class="btn secondary">Adicionar pelo link</button></div>
</form></details>

<div class="grid"><?php foreach($photos as $ph):?><article class="card"><img class="thumb" style="margin:0;height:220px" src="<?=e($ph['foto_url'])?>" alt="Foto do perfil"><form method="post" class="actions"><?=csrf_field()?><input type="hidden" name="action" value="principal"><input type="hidden" name="id" value="<?=$ph['id']?>"><button class="btn secondary" <?=$ph['principal']?'disabled':''?>><?=$ph['principal']?'Foto principal':'Usar como principal'?></button></form><form method="post" class="actions" onsubmit="return confirm('Remover esta foto?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$ph['id']?>"><button class="btn danger">Remover</button></form></article><?php endforeach;?></div>
<?php if(!$photos):?><div class="card empty">Sua galeria ainda está vazia.</div><?php endif;?>

<?php
$content=ob_get_clean();
$page='fotos'; $title='Fotos — Lindr'; require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap">
<?= $content ?>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>
