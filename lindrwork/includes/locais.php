<?php
/**
 * Estados e cidades do Lindr: o usuário ESCOLHE de uma lista, nunca digita.
 * Assim o banco só recebe nomes padronizados (nada de "criciuma", "Criciuma " ou "SC/Criciúma").
 *
 * - Os 27 estados (UF) estão completos.
 * - As cidades vêm de includes/municipios.json (lista completa do IBGE) quando esse arquivo existe.
 *   Para gerar o arquivo, rode UMA vez (com internet):  php tools/gerar_municipios.php
 * - Sem o arquivo, usa a lista resumida abaixo (capitais e principais cidades de cada estado).
 */

function lindr_ufs(): array {
    return [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
        'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];
}

/** Ordena nomes em português ignorando acentos (Águas vem junto do A, Içara junto do I). */
function lindr_ordenar(array $list): array {
    $fold = fn(string $s): string => mb_strtolower(strtr($s, [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
        'Á'=>'A','À'=>'A','Â'=>'A','Ã'=>'A','É'=>'E','Ê'=>'E','Í'=>'I','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ü'=>'U','Ç'=>'C',
    ]));
    usort($list, fn($a, $b) => strcmp($fold($a), $fold($b)));
    return $list;
}

/** Mapa UF => lista de cidades (ordenadas). */
function lindr_cidades(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $json = __DIR__ . '/municipios.json';
    if (is_file($json)) {
        $data = json_decode((string)file_get_contents($json), true);
        if (is_array($data) && count($data) >= 27) {
            foreach ($data as $uf => $list) {
                $data[$uf] = is_array($list) ? lindr_ordenar($list) : [];
            }
            return $cache = $data;
        }
    }

    $data = [
        'AC' => ['Rio Branco', 'Cruzeiro do Sul', 'Sena Madureira', 'Tarauacá', 'Feijó', 'Brasiléia', 'Senador Guiomard', 'Plácido de Castro'],
        'AL' => ['Maceió', 'Arapiraca', 'Rio Largo', 'Palmeira dos Índios', 'União dos Palmares', 'Penedo', 'São Miguel dos Campos', 'Delmiro Gouveia', 'Coruripe', 'Marechal Deodoro'],
        'AP' => ['Macapá', 'Santana', 'Laranjal do Jari', 'Oiapoque', 'Mazagão', 'Porto Grande'],
        'AM' => ['Manaus', 'Parintins', 'Itacoatiara', 'Manacapuru', 'Coari', 'Tefé', 'Tabatinga', 'Maués', 'Humaitá', 'São Gabriel da Cachoeira'],
        'BA' => ['Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Camaçari', 'Itabuna', 'Juazeiro', 'Lauro de Freitas', 'Ilhéus', 'Jequié', 'Teixeira de Freitas', 'Barreiras', 'Alagoinhas', 'Porto Seguro', 'Simões Filho', 'Paulo Afonso', 'Eunápolis', 'Santo Antônio de Jesus', 'Valença', 'Candeias', 'Guanambi'],
        'CE' => ['Fortaleza', 'Caucaia', 'Juazeiro do Norte', 'Maracanaú', 'Sobral', 'Crato', 'Itapipoca', 'Maranguape', 'Iguatu', 'Quixadá', 'Pacatuba', 'Aquiraz', 'Canindé', 'Russas', 'Tianguá'],
        'DF' => ['Brasília'],
        'ES' => ['Vitória', 'Vila Velha', 'Serra', 'Cariacica', 'Linhares', 'Cachoeiro de Itapemirim', 'Colatina', 'Guarapari', 'São Mateus', 'Aracruz', 'Viana', 'Nova Venécia', 'Barra de São Francisco', 'Marataízes'],
        'GO' => ['Goiânia', 'Aparecida de Goiânia', 'Anápolis', 'Rio Verde', 'Luziânia', 'Águas Lindas de Goiás', 'Valparaíso de Goiás', 'Trindade', 'Formosa', 'Novo Gama', 'Senador Canedo', 'Itumbiara', 'Catalão', 'Jataí', 'Planaltina', 'Caldas Novas'],
        'MA' => ['São Luís', 'Imperatriz', 'São José de Ribamar', 'Timon', 'Caxias', 'Codó', 'Paço do Lumiar', 'Açailândia', 'Bacabal', 'Balsas', 'Santa Inês', 'Pinheiro', 'Chapadinha'],
        'MT' => ['Cuiabá', 'Várzea Grande', 'Rondonópolis', 'Sinop', 'Tangará da Serra', 'Cáceres', 'Sorriso', 'Lucas do Rio Verde', 'Barra do Garças', 'Primavera do Leste', 'Alta Floresta', 'Nova Mutum', 'Campo Verde'],
        'MS' => ['Campo Grande', 'Dourados', 'Três Lagoas', 'Corumbá', 'Ponta Porã', 'Naviraí', 'Nova Andradina', 'Sidrolândia', 'Maracaju', 'Aquidauana', 'Paranaíba', 'Coxim', 'Rio Brilhante'],
        'MG' => ['Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim', 'Montes Claros', 'Ribeirão das Neves', 'Uberaba', 'Governador Valadares', 'Ipatinga', 'Sete Lagoas', 'Divinópolis', 'Santa Luzia', 'Ibirité', 'Poços de Caldas', 'Patos de Minas', 'Pouso Alegre', 'Teófilo Otoni', 'Barbacena', 'Sabará', 'Varginha', 'Conselheiro Lafaiete', 'Vespasiano', 'Itabira', 'Araguari', 'Passos', 'Ouro Preto', 'Lavras', 'São João del-Rei'],
        'PA' => ['Belém', 'Ananindeua', 'Santarém', 'Marabá', 'Castanhal', 'Parauapebas', 'Abaetetuba', 'Cametá', 'Marituba', 'Bragança', 'Altamira', 'Tucuruí', 'Barcarena', 'Paragominas', 'Redenção'],
        'PB' => ['João Pessoa', 'Campina Grande', 'Santa Rita', 'Patos', 'Bayeux', 'Sousa', 'Cabedelo', 'Cajazeiras', 'Guarabira', 'Sapé', 'Mamanguape', 'Queimadas'],
        'PR' => ['Curitiba', 'Londrina', 'Maringá', 'Ponta Grossa', 'Cascavel', 'São José dos Pinhais', 'Foz do Iguaçu', 'Colombo', 'Guarapuava', 'Paranaguá', 'Araucária', 'Toledo', 'Apucarana', 'Pinhais', 'Campo Largo', 'Almirante Tamandaré', 'Umuarama', 'Paranavaí', 'Piraquara', 'Cambé', 'Sarandi', 'Fazenda Rio Grande', 'Francisco Beltrão', 'Pato Branco', 'Cianorte', 'Telêmaco Borba', 'Castro', 'Rolândia'],
        'PE' => ['Recife', 'Jaboatão dos Guararapes', 'Olinda', 'Caruaru', 'Petrolina', 'Paulista', 'Cabo de Santo Agostinho', 'Camaragibe', 'Garanhuns', 'Vitória de Santo Antão', 'Igarassu', 'São Lourenço da Mata', 'Santa Cruz do Capibaribe', 'Abreu e Lima', 'Ipojuca', 'Serra Talhada', 'Araripina', 'Gravatá', 'Carpina', 'Goiana', 'Belo Jardim', 'Arcoverde', 'Ouricuri', 'Escada', 'Pesqueira', 'Surubim', 'Fernando de Noronha'],
        'PI' => ['Teresina', 'Parnaíba', 'Picos', 'Piripiri', 'Floriano', 'Campo Maior', 'Barras', 'União', 'Altos', 'Pedro II', 'Valença do Piauí', 'São Raimundo Nonato'],
        'RJ' => ['Rio de Janeiro', 'São Gonçalo', 'Duque de Caxias', 'Nova Iguaçu', 'Niterói', 'Belford Roxo', 'São João de Meriti', 'Campos dos Goytacazes', 'Petrópolis', 'Volta Redonda', 'Magé', 'Itaboraí', 'Mesquita', 'Nova Friburgo', 'Barra Mansa', 'Macaé', 'Cabo Frio', 'Angra dos Reis', 'Teresópolis', 'Resende', 'Queimados', 'Nilópolis', 'Maricá', 'Rio das Ostras', 'Araruama', 'Itaguaí', 'Búzios', 'Paraty'],
        'RN' => ['Natal', 'Mossoró', 'Parnamirim', 'São Gonçalo do Amarante', 'Macaíba', 'Ceará-Mirim', 'Caicó', 'Assú', 'Currais Novos', 'Santa Cruz', 'Nova Cruz', 'João Câmara', 'Extremoz'],
        'RS' => ['Porto Alegre', 'Caxias do Sul', 'Canoas', 'Pelotas', 'Santa Maria', 'Gravataí', 'Viamão', 'Novo Hamburgo', 'São Leopoldo', 'Rio Grande', 'Alvorada', 'Passo Fundo', 'Sapucaia do Sul', 'Uruguaiana', 'Santa Cruz do Sul', 'Cachoeirinha', 'Bagé', 'Bento Gonçalves', 'Erechim', 'Guaíba', 'Cachoeira do Sul', 'Santana do Livramento', 'Esteio', 'Ijuí', 'Sapiranga', 'Santo Ângelo', 'Lajeado', 'Venâncio Aires', 'Farroupilha', 'Gramado', 'Torres', 'Capão da Canoa'],
        'RO' => ['Porto Velho', 'Ji-Paraná', 'Ariquemes', 'Vilhena', 'Cacoal', 'Rolim de Moura', 'Jaru', 'Guajará-Mirim', 'Pimenta Bueno', 'Ouro Preto do Oeste'],
        'RR' => ['Boa Vista', 'Rorainópolis', 'Caracaraí', 'Alto Alegre', 'Mucajaí', 'Cantá', 'Pacaraima', 'Bonfim'],
        'SC' => ['Florianópolis', 'Joinville', 'Blumenau', 'São José', 'Chapecó', 'Itajaí', 'Criciúma', 'Jaraguá do Sul', 'Lages', 'Palhoça', 'Balneário Camboriú', 'Brusque', 'Tubarão', 'São Bento do Sul', 'Caçador', 'Camboriú', 'Navegantes', 'Concórdia', 'Rio do Sul', 'Araranguá', 'Gaspar', 'Biguaçu', 'Indaial', 'Itapema', 'Mafra', 'Canoinhas', 'Içara', 'Laguna', 'Imbituba', 'Xanxerê', 'São Miguel do Oeste', 'Videira', 'Joaçaba', 'Fraiburgo', 'Curitibanos', 'Pomerode', 'Timbó', 'Schroeder', 'Guaramirim', 'Barra Velha', 'Penha', 'Piçarras', 'Garopaba', 'Santo Amaro da Imperatriz', 'Tijucas', 'Sombrio', 'Morro da Fumaça', 'Forquilhinha', 'Nova Veneza', 'Cocal do Sul', 'Urussanga', 'Lauro Müller', 'Orleans', 'Braço do Norte', 'Capivari de Baixo', 'Jaguaruna', 'Maracajá', 'Turvo', 'Treviso', 'Siderópolis', 'Balneário Rincão', 'Balneário Arroio do Silva', 'Passo de Torres', 'São João do Sul', 'Praia Grande', 'Balneário Gaivota', 'Bombinhas', 'Governador Celso Ramos', 'Rancho Queimado', 'Urubici', 'São Joaquim', 'Bom Jardim da Serra'],
        'SP' => ['São Paulo', 'Guarulhos', 'Campinas', 'São Bernardo do Campo', 'Santo André', 'Osasco', 'São José dos Campos', 'Ribeirão Preto', 'Sorocaba', 'Mauá', 'São José do Rio Preto', 'Mogi das Cruzes', 'Santos', 'Diadema', 'Jundiaí', 'Piracicaba', 'Carapicuíba', 'Bauru', 'Itaquaquecetuba', 'São Vicente', 'Franca', 'Praia Grande', 'Guarujá', 'Taubaté', 'Limeira', 'Suzano', 'Taboão da Serra', 'Sumaré', 'Barueri', 'Embu das Artes', 'São Carlos', 'Marília', 'Indaiatuba', 'Cotia', 'Americana', 'Jacareí', 'Araraquara', 'Presidente Prudente', 'Hortolândia', 'Rio Claro', 'Araçatuba', 'Santa Bárbara d\'Oeste', 'Ferraz de Vasconcelos', 'Francisco Morato', 'Itapevi', 'Bragança Paulista', 'Pindamonhangaba', 'Itu', 'São Caetano do Sul', 'Ourinhos', 'Atibaia', 'Campos do Jordão', 'Ubatuba', 'São Sebastião'],
        'SE' => ['Aracaju', 'Nossa Senhora do Socorro', 'Lagarto', 'Itabaiana', 'São Cristóvão', 'Estância', 'Tobias Barreto', 'Simão Dias', 'Capela', 'Propriá'],
        'TO' => ['Palmas', 'Araguaína', 'Gurupi', 'Porto Nacional', 'Paraíso do Tocantins', 'Colinas do Tocantins', 'Guaraí', 'Tocantinópolis', 'Dianópolis', 'Formoso do Araguaia'],
    ];
    foreach ($data as $uf => $list) {
        $data[$uf] = lindr_ordenar($list);
    }
    return $cache = $data;
}

/** Cidades de um estado (lista vazia se a UF não existir). */
function lindr_cidades_da_uf(string $uf): array {
    $all = lindr_cidades();
    return $all[strtoupper($uf)] ?? [];
}

/**
 * Lê UF e cidade do formulário e confere se são ESCOLHAS válidas da lista.
 * Retorna [uf|null, cidade|null, erro|null]. A cidade volta com a grafia oficial da lista.
 * $legacy = [uf, cidade] atuais da pessoa: se ela não mexeu, aceitamos o valor antigo
 * (contas criadas antes da lista não perdem o local).
 */
function location_from_post(string $ufKey = 'state', string $cityKey = 'city', bool $required = false, array $legacy = []): array {
    $uf = strtoupper(trim((string)($_POST[$ufKey] ?? '')));
    $city = trim((string)($_POST[$cityKey] ?? ''));
    if ($uf === '' && $city === '') {
        return $required ? [null, null, 'Escolha seu estado e sua cidade.'] : [null, null, null];
    }
    if (!isset(lindr_ufs()[$uf])) return [null, null, 'Escolha um estado da lista.'];
    if ($city === '') return [null, null, 'Escolha a cidade.'];
    foreach (lindr_cidades_da_uf($uf) as $official) {
        if (mb_strtolower($official) === mb_strtolower($city)) return [$uf, $official, null];
    }
    if ($legacy && strtoupper((string)($legacy[0] ?? '')) === $uf && (string)($legacy[1] ?? '') === $city) {
        return [$uf, $city, null];
    }
    return [null, null, 'Escolha uma cidade da lista.'];
}

/**
 * Desenha os dois campos (Estado e Cidade) como listas de escolha.
 * Ao trocar o estado, a lista de cidades é carregada de cidades.php (sem recarregar a página).
 * $anyLabel: texto da opção vazia ("Todos", "Todas") para filtros; null = campo obrigatório.
 * $base: prefixo do caminho até cidades.php ('' na raiz, '../' dentro de admin/).
 */
function location_fields(string $uf = '', string $city = '', ?string $anyLabel = null, string $base = '', string $ufName = 'state', string $cityName = 'city', bool $inline = false): string {
    static $n = 0;
    $n++;
    $h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $required = $anyLabel === null;
    $uf = strtoupper($uf);
    $ufs = lindr_ufs();
    if (!isset($ufs[$uf])) $uf = '';
    $idUf = 'loc_uf_' . $n;
    $idCity = 'loc_city_' . $n;

    // $inline = true: formato do painel ADM (rótulo envolvendo o campo, sem a grade .loc-row)
    $o = $inline ? '<label>Estado' : '<div class="loc-row"><div><label for="' . $idUf . '">Estado</label>';
    $o .= '<select id="' . $idUf . '" name="' . $h($ufName) . '"' . ($required ? ' required' : '') . '>';
    $o .= '<option value="">' . $h($required ? 'Escolha o estado…' : $anyLabel) . '</option>';
    foreach ($ufs as $sigla => $nome) {
        $o .= '<option value="' . $sigla . '"' . ($sigla === $uf ? ' selected' : '') . '>' . $h($nome . ' (' . $sigla . ')') . '</option>';
    }
    $o .= $inline ? '</select></label><label>Cidade' : '</select></div><div><label for="' . $idCity . '">Cidade</label>';
    $o .= '<select id="' . $idCity . '" name="' . $h($cityName) . '"' . ($required ? ' required' : '') . ($uf === '' ? ' disabled' : '') . '>';
    $o .= '<option value="">' . $h($uf === '' ? 'Escolha o estado primeiro' : ($required ? 'Escolha a cidade…' : $anyLabel)) . '</option>';
    $list = $uf !== '' ? lindr_cidades_da_uf($uf) : [];
    // cidade antiga que não está na lista continua aparecendo (para não apagar o dado sem querer)
    if ($city !== '' && $uf !== '' && !in_array($city, $list, true)) array_unshift($list, $city);
    foreach ($list as $c) {
        $o .= '<option value="' . $h($c) . '"' . ($c === $city ? ' selected' : '') . '>' . $h($c) . '</option>';
    }
    $o .= $inline ? '</select></label>' : '</select></div></div>';
    $o .= '<script>(function(){var u=document.getElementById("' . $idUf . '"),c=document.getElementById("' . $idCity . '");'
        . 'var req=' . ($required ? 'true' : 'false') . ',any=' . json_encode($anyLabel ?? '', JSON_UNESCAPED_UNICODE) . ';'
        . 'u.addEventListener("change",function(){c.innerHTML="";var f=document.createElement("option");f.value="";'
        . 'if(!u.value){f.textContent=req?"Escolha o estado primeiro":any;c.appendChild(f);c.disabled=true;return}'
        . 'f.textContent="Carregando…";c.appendChild(f);c.disabled=true;'
        . 'fetch(' . json_encode($base . 'cidades.php?uf=', JSON_UNESCAPED_SLASHES) . '+encodeURIComponent(u.value)).then(function(r){return r.json()}).then(function(l){'
        . 'c.innerHTML="";var o=document.createElement("option");o.value="";o.textContent=req?"Escolha a cidade…":any;c.appendChild(o);'
        . 'l.forEach(function(n){var p=document.createElement("option");p.value=n;p.textContent=n;c.appendChild(p)});c.disabled=false})'
        . '.catch(function(){f.textContent="Não foi possível carregar. Recarregue a página.";});});})();</script>';
    return $o;
}

/** Texto do local de um evento: ponto de encontro (digitado) + cidade/UF (escolhidas). */
function event_place(array $g): string {
    $city = trim((string)($g['cidade'] ?? ''));
    $uf = trim((string)($g['estado'] ?? ''));
    $cityUf = $city !== '' ? $city . ($uf !== '' ? ', ' . $uf : '') : '';
    $spot = trim((string)($g['local_evento'] ?? ''));
    return trim($spot . ($spot !== '' && $cityUf !== '' ? ' — ' : '') . $cityUf);
}
