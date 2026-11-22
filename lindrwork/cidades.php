<?php
// Devolve as cidades de um estado em JSON (usado pelas listas Estado/Cidade).
require __DIR__ . '/includes/locais.php';
$uf = strtoupper(trim((string)($_GET['uf'] ?? '')));
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');
echo json_encode(lindr_cidades_da_uf($uf), JSON_UNESCAPED_UNICODE);
