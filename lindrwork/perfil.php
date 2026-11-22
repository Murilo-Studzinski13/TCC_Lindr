<?php
<<<<<<< HEAD
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/app_helpers.php';
require_once __DIR__ . '/includes/locais.php';
require_once __DIR__ . '/includes/opcoes.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $birth = trim($_POST['birth_date'] ?? '');
    $bd = DateTime::createFromFormat('!Y-m-d', $birth);
    if (!$bd || $bd->format('Y-m-d') !== $birth || $bd > new DateTime('today')) {
        flash('Informe uma data de nascimento válida.', 'error');
        redirect('perfil.php');
    }
    if (age($birth) < 18) {
        flash('Você precisa ter 18 anos ou mais.', 'error');
        redirect('perfil.php');
    }
    $photoUrl = trim($_POST['photo_url'] ?? '');
    if ($photoUrl !== '' && !valid_image_url($photoUrl)) {
        flash('A URL da foto precisa começar com http:// ou https:// e ter até 500 caracteres.', 'error');
        redirect('perfil.php');
    }
    // estado e cidade são escolhidos da lista (não digitados)
    [$uf, $cidade, $locErr] = location_from_post('state', 'city', true, [$me['estado'] ?? '', $me['cidade'] ?? '']);
    if ($locErr) {
        flash($locErr, 'error');
        redirect('perfil.php');
    }

    $fields = [
        'nome_social' => mb_substr(trim($_POST['social_name'] ?? ''), 0, 100)?:null,
        'foto_url' => $photoUrl?:null,
        'pronomes' => choice_from_post('pronouns', opcoes_pronomes(), $me['pronomes'] ?? null),
        'cidade' => $cidade,
        'estado' => $uf?:null,
        'bio' => mb_substr(trim($_POST['bio'] ?? ''), 0, 1000)?:null,
        'identidade_genero' => choice_from_post('gender_identity', opcoes_identidade(), $me['identidade_genero'] ?? null),
        'orientacao' => choice_from_post('orientation', opcoes_orientacao(), $me['orientacao'] ?? null),
        'visibilidade_identidade' => $_POST['visibility'] ?? 'privado',
        'procurando' => $_POST['looking_for'] ?? 'amizade',
        'data_nascimento' => $birth?:null
    ];

    $allowedVisibility = ['privado', 'amigos', 'publico'];
    $allowedLooking = ['amizade', 'grupo', 'eventos', 'conversar'];
    if (!in_array($fields['visibilidade_identidade'], $allowedVisibility, true)) $fields['visibilidade_identidade'] = 'privado';
    if (!in_array($fields['procurando'], $allowedLooking, true)) $fields['procurando'] = 'amizade';

    $set = [];
    $values = [];
    foreach ($fields as $key => $value) {
        $set[] = $key . '=?';
        $values[] = $value;
    }
    $values[] = $me['id'];
    $pdo->prepare('UPDATE usuarios SET ' . implode(',', $set) . ' WHERE id=?')->execute($values);

    $pdo->prepare('DELETE FROM usuario_interesses WHERE usuario_id=?')->execute([$me['id']]);
    $posted = is_array($_POST['interests'] ?? null)?$_POST['interests']:[];
    $selected = [];
    foreach ($posted as $interest) {
        if (!is_string($interest)) continue;
        $q = $pdo->prepare('SELECT id,nome FROM interesses WHERE nome=? AND ativo=1 LIMIT 1');
        $q->execute([$interest]);
        $row = $q->fetch();
        if ($row) {
            $pdo->prepare('INSERT IGNORE INTO usuario_interesses(usuario_id,interesse_id) VALUES(?,?)')
                ->execute([$me['id'], $row['id']]);
            $selected[] = $row['nome'];
        }
    }
    $pdo->prepare('UPDATE usuarios SET interesses=? WHERE id=?')->execute([implode(', ', $selected), $me['id']]);
    flash('Perfil salvo.');
    redirect('perfil.php');
}

$me = user();
$interests = get_interests();
$q = $pdo->prepare('SELECT i.nome FROM interesses i JOIN usuario_interesses ui ON ui.interesse_id=i.id WHERE ui.usuario_id=?');
$q->execute([$me['id']]);
$mine = $q->fetchAll(PDO::FETCH_COLUMN);
$ph = $pdo->prepare('SELECT * FROM fotos WHERE usuario_id=? ORDER BY principal DESC,ordem,id');
$ph->execute([$me['id']]);
$photos = $ph->fetchAll();
$pp = $pdo->prepare("SELECT id,conteudo,imagem_url,data_publicacao FROM publicacoes WHERE usuario_id=? AND imagem_url IS NOT NULL AND imagem_url<>'' ORDER BY data_publicacao DESC LIMIT 60");
$pp->execute([$me['id']]);
$postPhotos = $pp->fetchAll();
$blocked = $pdo->prepare('SELECT u.* FROM bloqueios b JOIN usuarios u ON u.id=b.bloqueado_id WHERE b.usuario_id=? ORDER BY b.data_bloqueio DESC');
$blocked->execute([$me['id']]);
$blocked = $blocked->fetchAll();
$page = 'perfil';
$title = 'Meu perfil — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap">
        <h2>Meu perfil</h2>
        <p><a class="btn secondary" href="fotos.php">Gerenciar galeria de fotos</a> <a class="btn secondary" href="configuracoes.php">Configurações e privacidade</a></p>
        <?php if (!$me['data_nascimento']): ?>
            <p class="mut">Falta só a data de nascimento para você começar a usar o Lindr.</p>
        <?php endif; ?>
        <section class="card" id="fotos" style="margin-top:14px">
            <div class="row" style="justify-content:space-between">
                <h3 style="margin:0">Minhas fotos <span class="mut" style="font-weight:400">(<?= count($photos) ?>/8)</span></h3>
                <a class="btn secondary sm" href="fotos.php">Gerenciar galeria</a>
            </div>
            <?php if ($photos): ?>
                <div class="gallery">
                    <?php foreach ($photos as $f): ?>
                        <div class="g-item"><img src="<?=e($f['foto_url'])?>" alt="Minha foto" loading="lazy">
                            <?php if ($f['principal']): ?>
                                <span class="tag">Principal</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mut">Você ainda não adicionou fotos. Escolha uma imagem do seu aparelho abaixo.</p>
            <?php endif; ?>
            <?php if (count($photos) < 8): ?>
                <form method="post" action="fotos.php" enctype="multipart/form-data" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="upload"><input type="hidden" name="voltar" value="perfil">
                    <label for="arquivo-perfil">Adicionar foto do meu aparelho</label>
                    <input id="arquivo-perfil" type="file" name="arquivo" accept="image/jpeg,image/png,image/webp" required>
                    <p class="mut" style="margin:6px 0 0">JPG, PNG ou WEBP, até 5 MB.</p>
                    <div class="actions"><button class="btn" type="submit">Enviar foto</button></div>
                </form>
            <?php endif; ?>
        </section>
        <section class="card" id="publicacoes" style="margin-top:14px">
            <div class="row" style="justify-content:space-between">
                <h3 style="margin:0">Minhas publicações com foto <span class="mut" style="font-weight:400">(<?= count($postPhotos) ?>)</span></h3>
                <a class="btn secondary sm" href="index.php">Publicar no feed</a>
            </div>
            <p class="mut" style="margin:6px 0 0">Todas as fotos que você postou no feed ficam aqui, e outras pessoas também veem no seu perfil.</p>
            <?php if ($postPhotos): ?>
                <div class="gallery">
                    <?php foreach ($postPhotos as $pf): ?>
                        <div class="g-item"><a href="<?=e($pf['imagem_url'])?>" target="_blank" rel="noopener"><img src="<?=e($pf['imagem_url'])?>" alt="<?=e(mb_strimwidth($pf['conteudo']?:'Publicação com foto',0,80,'…'))?>" loading="lazy"></a><span class="tag"><?= e(date('d/m/Y', strtotime($pf['data_publicacao']))) ?></span>
                            <form class="del" method="post" action="index.php" onsubmit="return confirm('Excluir esta publicação?')"><?= csrf_field() ?><input type="hidden" name="action" value="excluir_post"><input type="hidden" name="publicacao_id" value="<?=$pf['id']?>"><input type="hidden" name="voltar" value="perfil"><button class="btn danger sm" type="submit">Excluir</button></form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mut" style="margin-top:12px">Você ainda não postou fotos no feed.</p>
            <?php endif; ?>
        </section>
        <form class="card" method="post" style="margin-top:14px"><?= csrf_field() ?>
            <div class="row" style="margin-bottom:10px"><?= avatar($me) ?>
                <div><b><?= e(display_name($me)) ?></b>
                    <div class="mut"><?= e($me['email']) ?></div>
                </div>
            </div>
            <label>Data de nascimento</label>
            <input name="birth_date" type="date" value="<?=e($me['data_nascimento'])?>" max="<?=date('Y-m-d', strtotime('-18 years'))?>" required>
            <div class="two">
                <div>
                    <label>Nome social</label>
                    <input name="social_name" value="<?=e($me['nome_social'])?>">
                </div>
                <div>
                    <label>Pronomes</label>
                    <?= choice_select('pronouns', opcoes_pronomes(), $me['pronomes'] ?? '') ?>
                </div>
            </div>
            <?= location_fields((string)($me['estado'] ?? ''), (string)($me['cidade'] ?? '')) ?>
            <label>Foto URL</label>
            <input name="photo_url" value="<?=e($me['foto_url']??'')?>" placeholder="https://...">
            <label>Bio</label>
            <textarea name="bio" rows="3" maxlength="1000"><?=e($me['bio'])?></textarea>
            <div class="two">
                <div>
                    <label>Identidade de gênero</label>
                    <?= choice_select('gender_identity', opcoes_identidade(), $me['identidade_genero'] ?? '') ?>
                </div>
                <div>
                    <label>Orientação</label>
                    <?= choice_select('orientation', opcoes_orientacao(), $me['orientacao'] ?? '') ?>
                </div>
            </div>
            <div class="two">
                <div>
                    <label>Quem vê minha identidade</label>
                    <select name="visibility">
                    <option value="privado" <?=$me['visibilidade_identidade']==='privado'?'selected':''?>>Só eu</option>
                    <option value="amigos" <?=$me['visibilidade_identidade']==='amigos'?'selected':''?>>Meus matches</option>
                    <option value="publico" <?=$me['visibilidade_identidade']==='publico'?'selected':''?>>Todo mundo</option>
                    </select>
                </div>
                <div>
                    <label>Estou procurando</label>
                    <select name="looking_for">
                    <option value="amizade" <?=$me['procurando']==='amizade'?'selected':''?>>Amizade</option>
                    <option value="grupo" <?=$me['procurando']==='grupo'?'selected':''?>>Grupos</option>
                    <option value="eventos" <?=$me['procurando']==='eventos'?'selected':''?>>Eventos</option>
                    <option value="conversar" <?=$me['procurando']==='conversar'?'selected':''?>>Conversar</option>
                    </select>
                </div>
            </div>
            <label>Interesses</label>
            <div>
                <?php foreach ($interests as $i): ?>
                    <label style="display:inline-block;font-weight:400"><input style="width:auto" type="checkbox" name="interests[]" value="<?=e($i['nome'])?>" <?=in_array($i['nome'],$mine,true)?'checked':''?>> <?= e($i['nome']) ?></label>
                <?php endforeach; ?>
            </div>
            <div class="acts"><button class="btn" type="submit">Salvar perfil</button></div>
        </form>
        <div class="card" id="seguranca" style="margin-top:16px">
            <h3>Segurança</h3>
            <p class="mut">Bloqueios atuais</p>
            <?php if (!$blocked): ?>
                <p class="mut">Você não bloqueou ninguém.</p>
                <?php
            else:
                foreach ($blocked as $b):
                    ?>
                    <div class="li"><?= avatar($b) ?><b style="flex:1"><?= e(display_name($b)) ?></b>
                        <form method="post" action="bloquear.php"><?= csrf_field() ?><input type="hidden" name="action" value="unblock"><input type="hidden" name="target_id" value="<?=$b['id']?>"><button class="btn sm ghost" type="submit">Desbloquear</button></form>
                    </div>
                    <?php
                endforeach;
            endif;
            ?>
        </div>
    </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require __DIR__.'/config/database.php';
require __DIR__.'/includes/app_helpers.php';
$me=require_login();

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $birth=trim($_POST['birth_date']??'');
    $bd=DateTime::createFromFormat('!Y-m-d',$birth);
    if(!$bd||$bd->format('Y-m-d')!==$birth||$bd>new DateTime('today')){flash('Informe uma data de nascimento válida.','error');redirect('perfil.php');}
    if(age($birth)<18){flash('Você precisa ter 18 anos ou mais.','error');redirect('perfil.php');}
    $photoUrl=trim($_POST['photo_url']??'');
    if($photoUrl!==''&&!valid_image_url($photoUrl)){flash('A URL da foto precisa começar com http:// ou https:// e ter até 500 caracteres.','error');redirect('perfil.php');}
    $uf=strtoupper(trim($_POST['state']??''));
    if($uf!==''&&!preg_match('/^[A-Z]{2}$/',$uf)){flash('Use a sigla do estado com 2 letras (ex.: SC).','error');redirect('perfil.php');}

    $fields=[
        'nome_social'=>mb_substr(trim($_POST['social_name']??''),0,100)?:null,
        'foto_url'=>$photoUrl?:null,
        'pronomes'=>mb_substr(trim($_POST['pronouns']??''),0,30)?:null,
        'cidade'=>mb_substr(trim($_POST['city']??''),0,100)?:null,
        'estado'=>$uf?:null,
        'bio'=>mb_substr(trim($_POST['bio']??''),0,1000)?:null,
        'identidade_genero'=>mb_substr(trim($_POST['gender_identity']??''),0,50)?:null,
        'orientacao'=>mb_substr(trim($_POST['orientation']??''),0,50)?:null,
        'visibilidade_identidade'=>$_POST['visibility']??'privado',
        'procurando'=>$_POST['looking_for']??'amizade',
        'data_nascimento'=>$birth?:null
    ];

    $allowedVisibility=['privado','amigos','publico'];
    $allowedLooking=['amizade','grupo','eventos','conversar'];
    if(!in_array($fields['visibilidade_identidade'],$allowedVisibility,true))$fields['visibilidade_identidade']='privado';
    if(!in_array($fields['procurando'],$allowedLooking,true))$fields['procurando']='amizade';

    $set=[];$values=[];
    foreach($fields as $key=>$value){$set[]=$key.'=?';$values[]=$value;}
    $values[]=$me['id'];
    $pdo->prepare('UPDATE usuarios SET '.implode(',',$set).' WHERE id=?')->execute($values);

    $pdo->prepare('DELETE FROM usuario_interesses WHERE usuario_id=?')->execute([$me['id']]);
    $posted=is_array($_POST['interests']??null)?$_POST['interests']:[];
    $selected=[];
    foreach($posted as $interest){
        if(!is_string($interest))continue;
        $q=$pdo->prepare('SELECT id,nome FROM interesses WHERE nome=? AND ativo=1 LIMIT 1');$q->execute([$interest]);$row=$q->fetch();
        if($row){$pdo->prepare('INSERT IGNORE INTO usuario_interesses(usuario_id,interesse_id) VALUES(?,?)')->execute([$me['id'],$row['id']]);$selected[]=$row['nome'];}
    }
    $pdo->prepare('UPDATE usuarios SET interesses=? WHERE id=?')->execute([implode(', ',$selected),$me['id']]);
    flash('Perfil salvo.');redirect('perfil.php');
}

$me=user();
$interests=get_interests();
$q=$pdo->prepare('SELECT i.nome FROM interesses i JOIN usuario_interesses ui ON ui.interesse_id=i.id WHERE ui.usuario_id=?');$q->execute([$me['id']]);$mine=$q->fetchAll(PDO::FETCH_COLUMN);
$ph=$pdo->prepare('SELECT * FROM fotos WHERE usuario_id=? ORDER BY principal DESC,ordem,id');$ph->execute([$me['id']]);$photos=$ph->fetchAll();
$blocked=$pdo->prepare('SELECT u.* FROM bloqueios b JOIN usuarios u ON u.id=b.bloqueado_id WHERE b.usuario_id=? ORDER BY b.data_bloqueio DESC');$blocked->execute([$me['id']]);$blocked=$blocked->fetchAll();
$page='perfil';$title='Meu perfil — Lindr';require __DIR__.'/includes/header.php';require __DIR__.'/includes/navbar.php';
?>
<div class="pg"><main class="wrap"><h2>Meu perfil</h2><p><a class="btn secondary" href="fotos.php">Gerenciar galeria de fotos</a> <a class="btn secondary" href="configuracoes.php">Configurações e privacidade</a></p><?php if(!$me['data_nascimento']): ?><p class="mut">Falta só a data de nascimento para você começar a usar o Lindr.</p><?php endif; ?><section class="card" id="fotos" style="margin-top:14px"><div class="row" style="justify-content:space-between"><h3 style="margin:0">Minhas fotos <span class="mut" style="font-weight:400">(<?=count($photos)?>/8)</span></h3><a class="btn secondary sm" href="fotos.php">Gerenciar galeria</a></div><?php if($photos): ?><div class="gallery"><?php foreach($photos as $f): ?><div class="g-item"><img src="<?=e($f['foto_url'])?>" alt="Minha foto" loading="lazy"><?php if($f['principal']): ?><span class="tag">Principal</span><?php endif; ?></div><?php endforeach; ?></div><?php else: ?><p class="mut">Você ainda não adicionou fotos. Escolha uma imagem do seu aparelho abaixo.</p><?php endif; ?><?php if(count($photos)<8): ?><form method="post" action="fotos.php" enctype="multipart/form-data" style="margin-top:14px"><?=csrf_field()?><input type="hidden" name="action" value="upload"><input type="hidden" name="voltar" value="perfil"><label for="arquivo-perfil">Adicionar foto do meu aparelho</label><input id="arquivo-perfil" type="file" name="arquivo" accept="image/jpeg,image/png,image/webp" required><p class="mut" style="margin:6px 0 0">JPG, PNG ou WEBP, até 5 MB.</p><div class="actions"><button class="btn" type="submit">Enviar foto</button></div></form><?php endif; ?></section>
<form class="card" method="post" style="margin-top:14px"><?=csrf_field()?><div class="row" style="margin-bottom:10px"><?=avatar($me)?><div><b><?=e(display_name($me))?></b><div class="mut"><?=e($me['email'])?></div></div></div><label>Data de nascimento</label><input name="birth_date" type="date" value="<?=e($me['data_nascimento'])?>" required><div class="two"><div><label>Nome social</label><input name="social_name" value="<?=e($me['nome_social'])?>"></div><div><label>Pronomes</label><input name="pronouns" value="<?=e($me['pronomes'])?>" placeholder="ela/dela"></div></div><div class="two"><div><label>Cidade</label><input name="city" value="<?=e($me['cidade'])?>"></div><div><label>UF</label><input name="state" maxlength="2" pattern="[A-Za-z]{2}" value="<?=e($me['estado'])?>"></div></div><label>Foto URL</label><input name="photo_url" value="<?=e($me['foto_url']??'')?>" placeholder="https://..."><label>Bio</label><textarea name="bio" rows="3" maxlength="1000"><?=e($me['bio'])?></textarea><div class="two"><div><label>Identidade de gênero</label><input name="gender_identity" value="<?=e($me['identidade_genero'])?>"></div><div><label>Orientação</label><input name="orientation" value="<?=e($me['orientacao'])?>"></div></div><div class="two"><div><label>Quem vê minha identidade</label><select name="visibility"><option value="privado" <?=$me['visibilidade_identidade']==='privado'?'selected':''?>>Só eu</option><option value="amigos" <?=$me['visibilidade_identidade']==='amigos'?'selected':''?>>Meus matches</option><option value="publico" <?=$me['visibilidade_identidade']==='publico'?'selected':''?>>Todo mundo</option></select></div><div><label>Estou procurando</label><select name="looking_for"><option value="amizade" <?=$me['procurando']==='amizade'?'selected':''?>>Amizade</option><option value="grupo" <?=$me['procurando']==='grupo'?'selected':''?>>Grupos</option><option value="eventos" <?=$me['procurando']==='eventos'?'selected':''?>>Eventos</option><option value="conversar" <?=$me['procurando']==='conversar'?'selected':''?>>Conversar</option></select></div></div><label>Interesses</label><div><?php foreach($interests as $i): ?><label style="display:inline-block;font-weight:400"><input style="width:auto" type="checkbox" name="interests[]" value="<?=e($i['nome'])?>" <?=in_array($i['nome'],$mine,true)?'checked':''?>> <?=e($i['nome'])?></label><?php endforeach; ?></div><div class="acts"><button class="btn" type="submit">Salvar perfil</button></div></form><div class="card" id="seguranca" style="margin-top:16px"><h3>Segurança</h3><p class="mut">Bloqueios atuais</p><?php if(!$blocked): ?><p class="mut">Você não bloqueou ninguém.</p><?php else:foreach($blocked as $b): ?><div class="li"><?=avatar($b)?><b style="flex:1"><?=e(display_name($b))?></b><form method="post" action="bloquear.php"><?=csrf_field()?><input type="hidden" name="action" value="unblock"><input type="hidden" name="target_id" value="<?=$b['id']?>"><button class="btn sm ghost" type="submit">Desbloquear</button></form></div><?php endforeach;endif; ?></div></main></div><?php require __DIR__.'/includes/footer.php'; ?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
