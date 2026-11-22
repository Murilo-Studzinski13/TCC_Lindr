<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/app_helpers.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('perfil.php');
check_csrf();

$target = (int)($_POST['target_id'] ?? 0);
$action = $_POST['action'] ?? 'block';

if ($target < 1 || $target === (int)$me['id']) {
    flash('Pessoa inválida.', 'error');
    redirect('perfil.php');
}
$q = $pdo->prepare('SELECT id FROM usuarios WHERE id=?');
$q->execute([$target]);
if (!$q->fetchColumn()) {
    flash('Pessoa não encontrada.', 'error');
    redirect('perfil.php');
}

if ($action === 'unblock') {
    $pdo->prepare('DELETE FROM bloqueios WHERE usuario_id=? AND bloqueado_id=?')->execute([$me['id'], $target]);
    flash('Bloqueio removido.');
    redirect('perfil.php#seguranca');
}

$pdo->prepare('INSERT IGNORE INTO bloqueios(usuario_id,bloqueado_id) VALUES(?,?)')
    ->execute([$me['id'], $target]);
// Encerra amizade/pedidos pendentes entre as duas pessoas (o chat deixa de abrir).
$pdo->prepare('UPDATE matches SET status="bloqueado", bloqueado_por=? WHERE (usuario1_id=? AND usuario2_id=?) OR (usuario1_id=? AND usuario2_id=?)')
    ->execute([$me['id'], $me['id'], $target, $target, $me['id']]);

flash('Pessoa bloqueada. Ela não poderá mais ver seu perfil nem falar com você.');
redirect('descobrir.php');
