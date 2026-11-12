<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
$me = require_login();

$targetId = (int)($_GET['usuario_id'] ?? $_POST['usuario_id'] ?? 0);
$postId   = (int)($_GET['publicacao_id'] ?? $_POST['publicacao_id'] ?? 0);

$q = $pdo->prepare('SELECT id,nome,nome_social FROM usuarios WHERE id=?');
$q->execute([$targetId]);
$target = $q->fetch();
if (!$target || $targetId === (int)$me['id']) {
    flash('Não foi possível abrir a denúncia.', 'error');
    redirect('index.php');
}

// A publicação só entra na denúncia se realmente for da pessoa denunciada.
if ($postId) {
    $q = $pdo->prepare('SELECT 1 FROM publicacoes WHERE id=? AND usuario_id=?');
    $q->execute([$postId, $targetId]);
    if (!$q->fetchColumn()) $postId = 0;
}

// Mensagens trocadas entre denunciante e denunciado (em qualquer status do match).
$convIds = $pdo->prepare('SELECT id FROM matches WHERE (usuario1_id=? AND usuario2_id=?) OR (usuario1_id=? AND usuario2_id=?)');
$convIds->execute([$me['id'], $targetId, $targetId, $me['id']]);
$convIds = array_map('intval', $convIds->fetchAll(PDO::FETCH_COLUMN));
$temConversa = false;
if ($convIds) {
    $in = implode(',', $convIds); // só inteiros vindos do banco
    $temConversa = (bool)$pdo->query("SELECT 1 FROM mensagens WHERE match_id IN ($in) LIMIT 1")->fetchColumn();
}
$veioDoChat = !empty($_GET['chat']) || !empty($_POST['chat']);

$motivos = [
    'assedio'            => 'Assédio ou ameaça',
    'discurso_odio'      => 'Discurso de ódio',
    'perfil_falso'       => 'Perfil falso',
    'spam'               => 'Spam ou golpe',
    'conteudo_improprio' => 'Conteúdo impróprio',
    'outro'              => 'Outro motivo',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $motivo = $_POST['motivo'] ?? '';
    $desc   = trim($_POST['descricao'] ?? '');
    if (!isset($motivos[$motivo])) {
        flash('Escolha um motivo.', 'error');
        redirect('denunciar.php?usuario_id='.$targetId.($postId ? '&publicacao_id='.$postId : '').($veioDoChat ? '&chat=1' : ''));
    }
    $pdo->prepare('INSERT INTO denuncias(denunciante_id,denunciado_id,publicacao_id,motivo,descricao) VALUES(?,?,?,?,?)')
        ->execute([$me['id'], $targetId, $postId ?: null, $motivo, $desc !== '' ? mb_substr($desc, 0, 1000) : null]);
    $denunciaId = (int)$pdo->lastInsertId();

    // Prova: recorte das últimas 30 mensagens, copiado agora. É só isso que o administrador poderá ver.
    if ($temConversa && !empty($_POST['anexar_conversa'])) {
        try {
            $msgs = $pdo->query("SELECT remetente_id, conteudo, data_envio FROM mensagens WHERE match_id IN ($in) ORDER BY data_envio DESC, id DESC LIMIT 30")->fetchAll();
            $ins = $pdo->prepare('INSERT INTO denuncia_evidencias(denuncia_id,remetente_id,conteudo,data_envio) VALUES(?,?,?,?)');
            foreach (array_reverse($msgs) as $m) $ins->execute([$denunciaId, $m['remetente_id'], $m['conteudo'], $m['data_envio']]);
        } catch (PDOException $e) {
            error_log('Lindr: tabela denuncia_evidencias ausente (rode evidencias_migration.sql)');
        }
    }

    if (!empty($_POST['bloquear'])) {
        $pdo->prepare('INSERT IGNORE INTO bloqueios(usuario_id,bloqueado_id) VALUES(?,?)')->execute([$me['id'], $targetId]);
        $pdo->prepare('UPDATE matches SET status="bloqueado", bloqueado_por=? WHERE (usuario1_id=? AND usuario2_id=?) OR (usuario1_id=? AND usuario2_id=?)')
            ->execute([$me['id'], $me['id'], $targetId, $targetId, $me['id']]);
    }
    flash('Denúncia enviada. Nossa equipe vai analisar.');
    redirect('index.php');
}

$page = 'denunciar'; $title = 'Denunciar — Lindr';
require __DIR__.'/includes/header.php';
require __DIR__.'/includes/navbar.php';
?>
<div class="pg"><main class="wrap" style="max-width:560px">
  <div class="card">
    <h1>Denunciar</h1>
    <p class="mut">Você está denunciando <b><?=e(display_name($target))?></b><?=$postId ? ' por uma publicação' : ''?>. A pessoa não é avisada.</p>
    <p class="notice">🔒 A equipe <b>não lê as conversas</b> de ninguém. Ela só vê o que você decidir anexar a esta denúncia.</p>
    <form method="post">
      <?=csrf_field()?>
      <input type="hidden" name="usuario_id" value="<?=$targetId?>">
      <input type="hidden" name="publicacao_id" value="<?=$postId?>">
      <input type="hidden" name="chat" value="<?=$veioDoChat?1:0?>">
      <label for="motivo">Motivo</label>
      <select id="motivo" name="motivo" required>
        <option value="">Selecione…</option>
        <?php foreach ($motivos as $k => $v): ?><option value="<?=$k?>"><?=e($v)?></option><?php endforeach; ?>
      </select>
      <label for="descricao">O que aconteceu? (opcional)</label>
      <textarea id="descricao" name="descricao" rows="4" maxlength="1000"></textarea>
      <?php if ($temConversa): ?>
      <label class="checkbox-line"><input type="checkbox" name="anexar_conversa" value="1" <?=$veioDoChat?'checked':''?>> Anexar as últimas 30 mensagens da conversa como prova</label>
      <p class="mut" style="margin:2px 0 0">Assim a equipe vê exatamente o que aconteceu. Só essas mensagens serão compartilhadas.</p>
      <?php endif; ?>
      <label class="checkbox-line"><input type="checkbox" name="bloquear" value="1"> Bloquear esta pessoa também</label>
      <div class="actions">
        <button class="btn" type="submit">Enviar denúncia</button>
        <a class="btn secondary" href="javascript:history.back()">Cancelar</a>
      </div>
    </form>
  </div>
</main></div>
<?php require __DIR__.'/includes/footer.php'; ?>
