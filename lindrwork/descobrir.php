<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/app_helpers.php';
require_once __DIR__ . '/includes/locais.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $target = (int)($_POST['target_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $targetQ = $pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');
    $targetQ->execute([$target]);
    $targetUser = $targetQ->fetch();

    if ($targetUser && in_array($action, ['like', 'pass'], true) && $target !== (int)$me['id']) {
        if (!blocked_between((int)$me['id'], $target)) {
            $s = $pdo->prepare('SELECT id FROM matches WHERE usuario1_id=? AND usuario2_id=? LIMIT 1');
            $s->execute([$me['id'], $target]);
            $old = $s->fetchColumn();

            if (!$old) {
                $status = $action === 'like' ? 'pendente' : 'rejeitado';
                $pdo->prepare('INSERT INTO matches(usuario1_id,usuario2_id,status) VALUES(?,?,?)')
                    ->execute([$me['id'], $target, $status]);
                $newId = (int)$pdo->lastInsertId();

                if ($action === 'like') {
                    $s = $pdo->prepare('SELECT id FROM matches WHERE usuario1_id=? AND usuario2_id=? AND status="pendente" LIMIT 1');
                    $s->execute([$target, $me['id']]);
                    $reverseId = $s->fetchColumn();
                    if ($reverseId) {
                        $pdo->prepare('UPDATE matches SET status="match",data_match=NOW() WHERE id IN (?,?)')
                            ->execute([$newId, $reverseId]);
                        notify_user(
                            (int)$target,
                            'match',
                            'Vocês deram match!',
                            'Você e ' . display_name($me) . ' podem conversar.',
                            'matches.php',
                            (int)$me['id']
                        );
                        notify_user(
                            (int)$me['id'],
                            'match',
                            'Vocês deram match!',
                            'Você e ' . display_name($targetUser) . ' podem conversar.',
                            'matches.php',
                            (int)$target
                        );
                        flash('É um match! Já podem conversar.');
                    } else {
                        notify_user(
                            $target,
                            'convite',
                            'Alguém quer ser sua amizade',
                            display_name($me) . ' curtiu seu perfil. Veja em Amizades.',
                            'matches.php',
                            (int)$me['id']
                        );
                        flash('Curtida enviada.');
                    }
                }
            }
        }
    }
    redirect('descobrir.php');
}

// filtro de local: estado e cidade escolhidos da lista (valores inválidos são ignorados)
$ufFilter = strtoupper(trim($_GET['uf'] ?? ''));
if (!isset(lindr_ufs()[$ufFilter])) $ufFilter = '';
$cityFilter = trim($_GET['cidade'] ?? '');
if ($ufFilter === '' || !in_array($cityFilter, lindr_cidades_da_uf($ufFilter), true)) $cityFilter = '';
$interestFilter = trim($_GET['interesse'] ?? '');
$lookingFilter = $_GET['procurando'] ?? '';
$minAge = max(18, min(100, (int)($_GET['idade_min'] ?? 18)));
$maxAge = max($minAge, min(100, (int)($_GET['idade_max'] ?? 99)));
$sql = 'SELECT u.* FROM usuarios u WHERE u.id<>? AND u.ativo=1 AND u.discover_enabled=1 AND u.data_nascimento IS NOT NULL AND TIMESTAMPDIFF(YEAR,u.data_nascimento,CURDATE()) BETWEEN ? AND ?';
$params = [$me['id'], $minAge, $maxAge];
if ($ufFilter !== '') {
    $sql .= ' AND u.estado=?';
    $params[] = $ufFilter;
}
if ($cityFilter !== '') {
    $sql .= ' AND u.cidade=?';
    $params[] = $cityFilter;
}
if (in_array($lookingFilter, ['amizade', 'grupo', 'eventos', 'conversar'], true)) {
    $sql .= ' AND u.procurando=?';
    $params[] = $lookingFilter;
}
if ($interestFilter !== '') {
    $sql .= ' AND (u.interesses LIKE ? OR EXISTS (SELECT 1 FROM usuario_interesses ui JOIN interesses i ON i.id=ui.interesse_id WHERE ui.usuario_id=u.id AND i.nome LIKE ?))';
    $params[] = '%' . $interestFilter . '%';
    $params[] = '%' . $interestFilter . '%';
}
$sql .= ' AND NOT EXISTS (SELECT 1 FROM bloqueios b WHERE b.usuario_id=? AND b.bloqueado_id=u.id) AND NOT EXISTS (SELECT 1 FROM bloqueios b WHERE b.usuario_id=u.id AND b.bloqueado_id=?) AND NOT EXISTS (SELECT 1 FROM matches m WHERE (m.usuario1_id=? AND m.usuario2_id=u.id) OR (m.usuario1_id=u.id AND m.usuario2_id=? AND m.status="match")) ORDER BY u.id LIMIT 100';
$params[] = $me['id'];
$params[] = $me['id'];
$params[] = $me['id'];
$params[] = $me['id'];
$s = $pdo->prepare($sql);
$s->execute($params);
$candidates = $s->fetchAll();
$candidate = $candidates[0] ?? null;

$page = 'descobrir';
$title = 'Descobrir — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap">
        <h2>Descobrir</h2>
        <p class="mut">Conheça pessoas novas, faça amizades e encontre interesses em comum.</p>
        <form class="card" method="get">
            <?= location_fields($ufFilter, $cityFilter, 'Todos', '', 'uf', 'cidade') ?>
            <div class="field-row">
                <div>
                    <label>Interesse</label>
                    <select name="interesse">
                        <option value="">Todos</option>
                        <?php foreach (get_interests() as $in): ?>
                            <option value="<?=e($in['nome'])?>" <?=$interestFilter===$in['nome']?'selected':''?>><?= e($in['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div>
                    <label>Idade mínima</label>
                    <input type="number" name="idade_min" min="18" max="100" value="<?=$minAge?>">
                </div>
                <div>
                    <label>Idade máxima</label>
                    <input type="number" name="idade_max" min="18" max="100" value="<?=$maxAge?>">
                </div>
            </div>
            <label>Objetivo</label>
            <select name="procurando">
            <option value="">Todos</option>
            <?php
            foreach (['amizade' => 'Amizade', 'grupo' => 'Grupos', 'eventos' => 'Eventos', 'conversar' => 'Conversar'] as $k => $v):
                ?>
                <option value="<?=$k?>" <?=$lookingFilter===$k?'selected':''?>><?= $v ?></option>
            <?php endforeach; ?>
            </select>
            <div class="actions"><button class="btn">Aplicar filtros</button><a class="btn secondary" href="descobrir.php">Limpar</a></div>
        </form>
        <?php if (!$candidate): ?>
            <div class="card" style="text-align:center;padding:40px">Você viu todo mundo por agora.<br>Volte mais tarde ou explore as comunidades.</div>
        <?php else: ?>
            <div class="card" style="max-width:380px;margin:24px auto">
                <div style="height:270px;border-radius:16px;background:linear-gradient(135deg,#5b3f8f,#f4a3b8);position:relative;overflow:hidden">
                    <?php if (!empty($candidate['foto_url'])): ?>
                        <img src="<?=e($candidate['foto_url'])?>" alt="<?=e(display_name($candidate))?>" style="width:100%;height:100%;object-fit:cover">
                    <?php endif; ?>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="target_id" value="<?=$candidate['id']?>"><button class="dots" name="action" value="pass" type="submit">×</button></form>
                </div>
                <h3 style="margin-top:14px"><?= e(display_name($candidate)) ?>, <?= age($candidate['data_nascimento']) ?>
                    <?php if ($candidate['verificado']): ?>
                        <?= chip('✓ verificado', true) ?>
                    <?php endif; ?>
                </h3>
                <p class="mut"><?= icon('pin', 13) ?> <?= e(location_text($candidate)) ?> · <?= e($candidate['pronomes']) ?></p>
                <p><?= e($candidate['bio'] ?: 'Ainda sem bio.') ?></p>
                <p><a href="perfil_publico.php?id=<?=$candidate['id']?>">Ver perfil completo →</a></p>
                <div>
                    <?php
                    foreach (array_filter(array_map('trim', explode(',', (string)$candidate['interesses']))) as $interest):
                        echo chip($interest);
                    endforeach;
                    ?>
                    <?php
                    $q = $pdo->prepare('SELECT i.nome FROM interesses i JOIN usuario_interesses ui ON ui.interesse_id=i.id WHERE ui.usuario_id=? ORDER BY i.nome');
                    $q->execute([$candidate['id']]);
                    foreach ($q as $i) echo chip($i['nome']);
                    ?>
                </div>
                <form method="post" class="acts"><?= csrf_field() ?><input type="hidden" name="target_id" value="<?=$candidate['id']?>"><button class="btn ghost" name="action" value="pass" type="submit">× Passar</button><button class="btn" name="action" value="like" type="submit">♥ Curtir</button></form>
            </div>
        <?php endif; ?>
    </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
