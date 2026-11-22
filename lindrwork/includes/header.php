<?php
require_once __DIR__ . '/../config/database.php';
$page = $page ?? pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
$title = $title ?? 'Lindr — amizades LGBTQIA+';
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css?v=<?=@filemtime(__DIR__.'/../css/base.css')?>">
    <?php if (!in_array($page, ['base', 'social'], true) && is_file(__DIR__ . '/../css/' . $page . '.css')): ?>
        <link rel="stylesheet" href="css/<?=e($page)?>.css?v=<?=@filemtime(__DIR__.'/../css/'.$page.'.css')?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/social.css?v=<?=@filemtime(__DIR__.'/../css/social.css')?>">
</head>
<body>
