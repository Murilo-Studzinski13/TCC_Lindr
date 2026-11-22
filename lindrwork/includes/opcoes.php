<?php
/**
 * Listas de escolha do perfil (identidade, orientação, pronomes).
 * Escolher de uma lista evita texto digitado de formas diferentes no banco.
 * Quem já tinha um valor antigo digitado não o perde: ele continua aparecendo como opção.
 */

function opcoes_identidade(): array {
    return ['Mulher cis', 'Homem cis', 'Mulher trans', 'Homem trans', 'Travesti', 'Pessoa não binária', 'Gênero fluido', 'Agênero', 'Outra identidade'];
}

function opcoes_orientacao(): array {
    return ['Lésbica', 'Gay', 'Bissexual', 'Pansexual', 'Assexual', 'Heterossexual', 'Queer', 'Outra orientação'];
}

function opcoes_pronomes(): array {
    return ['ela/dela', 'ele/dele', 'elu/delu', 'ela/elu', 'ele/elu', 'qualquer pronome'];
}

/** Desenha um <select> com a opção vazia ("Prefiro não informar") e o valor atual preservado. */
function choice_select(string $name, array $options, ?string $current, string $emptyLabel = 'Prefiro não informar'): string {
    $h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $current = (string)$current;
    if ($current !== '' && !in_array($current, $options, true)) $options[] = $current;
    $o = '<select name="' . $h($name) . '"><option value="">' . $h($emptyLabel) . '</option>';
    foreach ($options as $opt) {
        $o .= '<option value="' . $h($opt) . '"' . ($opt === $current ? ' selected' : '') . '>' . $h($opt) . '</option>';
    }
    return $o . '</select>';
}

/** Valor enviado pelo formulário: só vale se estiver na lista (ou se for o valor antigo da pessoa). Vazio = null. */
function choice_from_post(string $key, array $options, ?string $current = null): ?string {
    $v = trim((string)($_POST[$key] ?? ''));
    if ($v === '') return null;
    if (in_array($v, $options, true)) return $v;
    if ($current !== null && $v === $current) return $v;
    return null;
}
