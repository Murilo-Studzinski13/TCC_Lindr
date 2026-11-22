<?php
declare(strict_types = 1);

$host = getenv('LINDR_DB_HOST') ?: '127.0.0.1';
$db = getenv('LINDR_DB_NAME') ?: 'lindr';
$user = getenv('LINDR_DB_USER') ?: 'root';
$pass = getenv('LINDR_DB_PASS') ?: '';
$charset = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados. Configure config/database.php ou as variáveis LINDR_DB_* antes de usar o Lindr.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax',]);
    session_start();
}

/** Regra única de senha (cadastro, troca e redefinição). Retorna a mensagem de erro ou null. */
function password_error(string $pass): ?string {
    if (strlen($pass) < 10 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
        return 'Use uma senha de pelo menos 10 caracteres, com letras e números.';
    }
    return null;
}

/** Aceita apenas URLs http(s) com tamanho compatível com as colunas VARCHAR(500). */
function valid_image_url(string $url): bool {
    if ($url === '' || strlen($url) > 500) return false;
    // imagens que já fazem parte do site (ex.: img/avatars/u1003.svg), só dentro de img/ e sem ".."
    if (preg_match('#^img/[A-Za-z0-9_./-]+\.(?:svg|png|jpe?g|webp|gif)$#', $url) && strpos($url, '..') === false) return true;
    // fotos enviadas pelo próprio usuário (arquivo salvo em uploads/fotos/ com nome aleatório)
    if (preg_match('#^uploads/fotos/[a-f0-9]{32}\.(?:png|jpe?g|webp)$#', $url)) return true;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    return in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
}

/** Existe bloqueio entre as duas pessoas, em qualquer direção? */
function blocked_between(int $a, int $b): bool {
    global $pdo;
    $s = $pdo->prepare('SELECT 1 FROM bloqueios WHERE (usuario_id=? AND bloqueado_id=?) OR (usuario_id=? AND bloqueado_id=?) LIMIT 1');
    $s->execute([$a, $b, $b, $a]);
    return (bool)$s->fetchColumn();
}

/** URL base do site. Defina LINDR_APP_URL em produção (evita Host header injection no e-mail de senha). */
function app_url(): string {
    $cfg = getenv('LINDR_APP_URL');
    if ($cfg) return rtrim($cfg, '/');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    return ($https ? 'https' : 'http') . '://' . $host . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}

function user(): ?array {
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    static $u = null;
    if ($u === null) {
        $s = $pdo->prepare('SELECT * FROM usuarios WHERE id=? AND ativo=1');
        $s->execute([(int)$_SESSION['user_id']]);
        $u = $s->fetch() ?: false;
    }
    return $u ?: null;
}

function require_login(): array {
    $u = user();
    if (!$u) redirect('login.php');
    return $u;
}

function is_admin(): bool {
    $u = user();
    return $u !== null && ($u['nivel_acesso'] ?? 'usuario') === 'admin';
}

function require_admin(): array {
    $u = user();
    if (!$u) redirect('login.php');
    if (($u['nivel_acesso'] ?? 'usuario') !== 'admin') {
        http_response_code(403);
        exit('Acesso restrito ao painel administrativo.');
    }
    return $u;
}

function admin_log(string $acao, ?string $entidade = null, ?int $entidadeId = null, ?string $detalhes = null): void {
    global $pdo;
    $u = user();
    if (!$u || ($u['nivel_acesso'] ?? 'usuario') !== 'admin') return;
    $s = $pdo->prepare('INSERT INTO auditoria_admin (admin_id, acao, entidade, entidade_id, detalhes) VALUES (?,?,?,?,?)');
    $s->execute([(int)$u['id'], $acao, $entidade, $entidadeId, $detalhes]);
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function age(string $birth): int {
    return (new DateTime($birth))->diff(new DateTime())->y;
}

function location_text(array $u): string {
    return trim(($u['cidade'] ?? '') . (!empty($u['estado']) ? ', ' . $u['estado'] : ''));
}

function display_name(array $u): string {
    return $u['nome_social'] ?: $u['nome'];
}

function initials(string $name): string {
    return strtoupper(mb_substr(trim($name), 0, 1));
}

function avatar(array $u, bool $small = false): string {
    $class = $small ? 'av s' : 'av';
    if (!empty($u['foto_url'])) return '<img class="' . $class . ' avatar-photo" src="' . e($u['foto_url']) . '" alt="' . e(display_name($u)) . '">';
    $colors = [
        '#5b3f8f,#f4a3b8',
        '#3b6fd9,#7be0c3',
        '#d9633b,#ffd07a',
        '#2f9e6e,#a8e6cf',
        '#8b5cf6,#f0abfc',
        '#e0457b,#ffb36b'
    ];
    $bg = $colors[((int)$u['id']) % count($colors)];
    return '<div class="' . $class . '" style="background:linear-gradient(135deg,' . $bg . ')">' . e(initials(display_name($u))) . '</div>';
}

function icon(string $name, int $size = 18): string {
    $paths = [
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.2l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'pin' => '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'msg' => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>',
        'send' => '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'spark' => '<path d="M12 3l1.9 5.6 5.6 1.9-5.6 1.9L12 18l-1.9-5.6-5.6-1.9 5.6-1.9z"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'cal' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'more' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        'arr' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'back' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'
    ];
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;flex:none">' . ($paths[$name] ?? '') . '</svg>';
}

function chip(string $text, bool $on = false): string {
    return '<span class="chip ' . ($on?'on':'') . '">' . e($text) . '</span>';
}
