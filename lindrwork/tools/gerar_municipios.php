<?php
/**
 * Gera includes/municipios.json com TODOS os municípios do Brasil (fonte: API de localidades do IBGE).
 * Rode uma vez, com internet, na pasta do projeto:   php tools/gerar_municipios.php
 * Depois disso o site passa a oferecer todas as cidades de cada estado.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../includes/locais.php';

function baixar(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => true]);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r === false ? null : (string)$r;
    }
    $r = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 30]]));
    return $r === false ? null : $r;
}

$out = [];
$total = 0;
foreach (array_keys(lindr_ufs()) as $uf) {
    $raw = baixar('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' . $uf . '/municipios');
    $list = $raw ? json_decode($raw, true) : null;
    if (!is_array($list) || !$list) {
        fwrite(STDERR, "Falhou ao baixar $uf. Confira a internet e rode de novo.\n");
        exit(1);
    }
    $names = array_values(array_filter(array_map(fn($m) => $m['nome'] ?? null, $list)));
    $names = lindr_ordenar($names);
    $out[$uf] = $names;
    $total += count($names);
    echo "$uf: " . count($names) . " municípios\n";
}
file_put_contents(__DIR__ . '/../includes/municipios.json', json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Pronto: $total municípios em includes/municipios.json\n";
