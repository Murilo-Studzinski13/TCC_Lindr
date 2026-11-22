<?php
/**
 * Telinha de testes do Lindr: confere o ambiente e envia um e-mail de teste.
 * Só funciona em localhost (no servidor de verdade ela responde 404). Pode apagar depois da apresentação.
 */
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit('Não encontrado.');
}
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$checks = [];
$add = function (string $nome, bool $ok, string $detalhe = '') use (&$checks) {
    $checks[] = [$nome, $ok, $detalhe];
};

// ---- PHP ----
$add('PHP 8 ou mais novo', version_compare(PHP_VERSION, '8.0.0', '>='), 'versão ' . PHP_VERSION);
foreach (['pdo_mysql', 'mbstring', 'openssl', 'curl'] as $ext) {
    $add('Extensão ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'ativa' : 'desligada no php.ini');
}

// ---- Banco ----
$pdo = null;
try {
    $dbName = getenv('LINDR_DB_NAME') ?: 'lindr';
    $pdo = new PDO('mysql:host=' . (getenv('LINDR_DB_HOST') ?: '127.0.0.1') . ';dbname=' . $dbName . ';charset=utf8mb4',
        getenv('LINDR_DB_USER') ?: 'root', getenv('LINDR_DB_PASS') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $add('Conexão com o banco "' . $dbName . '"', true, 'ok');
} catch (Throwable $e) {
    $add('Conexão com o banco', false, $e->getMessage());
}
if ($pdo) {
    $col = function (string $t, string $c) use ($pdo, $dbName): bool {
        $s = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        $s->execute([$dbName, $t, $c]);
        return (int)$s->fetchColumn() > 0;
    };
    $tab = function (string $t) use ($pdo, $dbName): bool {
        $s = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
        $s->execute([$dbName, $t]);
        return (int)$s->fetchColumn() > 0;
    };
    $fix = 'rode o melhorias_migration.sql';
    $add('usuarios.email_confirmado_em', $col('usuarios', 'email_confirmado_em'), $fix);
    $add('codigos_verificacao.tentativas', $col('codigos_verificacao', 'tentativas'), $fix);
    $add('grupos.cidade e grupos.estado', $col('grupos', 'cidade') && $col('grupos', 'estado'), $fix);
    $add('Tabela dispositivos_confiaveis', $tab('dispositivos_confiaveis'), $fix);
    try {
        $n = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        $add('Usuários cadastrados', true, $n . ' no banco');
    } catch (Throwable $e) {
        $add('Tabela usuarios', false, 'rode o database.sql');
    }
}

// ---- E-mail ----
$cfg = require __DIR__ . '/config/mail.php';
$add('PHPMailer em vendor/', is_file(__DIR__ . '/vendor/autoload.php') || is_file(__DIR__ . '/vendor/PHPMailer/src/PHPMailer.php'), 'vendor/PHPMailer/src/PHPMailer.php');
$add('SMTP preenchido (config/mail.local.php)', (bool)($cfg['username'] && $cfg['password'] && $cfg['from_email']), $cfg['username'] ? 'conta: ' . $cfg['username'] : 'usuário/senha vazios');

// ---- Listas de local ----
require_once __DIR__ . '/includes/locais.php';
$totalCidades = array_sum(array_map('count', lindr_cidades()));
$add('Estados', count(lindr_ufs()) === 27, count(lindr_ufs()) . ' de 27');
$add('Cidades (lista completa do IBGE)', is_file(__DIR__ . '/includes/municipios.json'), $totalCidades . ' cidades' . (is_file(__DIR__ . '/includes/municipios.json') ? '' : ' (lista resumida; rode php tools/gerar_municipios.php para todas)'));

// ---- Envio de teste ----
$envio = null;
$log = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && filter_var(trim($_POST['para'] ?? ''), FILTER_VALIDATE_EMAIL)) {
    $para = trim($_POST['para']);
    try {
        $base = __DIR__ . '/vendor/PHPMailer/src/';
        if (is_file(__DIR__ . '/vendor/autoload.php')) require_once __DIR__ . '/vendor/autoload.php';
        else {
            require_once $base . 'Exception.php';
            require_once $base . 'PHPMailer.php';
            require_once $base . 'SMTP.php';
        }
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['username'];
        $mail->Password = $cfg['password'];
        $mail->Port = $cfg['port'];
        $mail->SMTPSecure = $cfg['encryption'] === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 15;
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function ($str, $level) use (&$log) { $log .= $str; };
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($para);
        $mail->Subject = 'Teste de e-mail — Lindr';
        $mail->Body = 'Se você recebeu esta mensagem, o envio de e-mails do Lindr está funcionando. 🏳️‍🌈';
        $mail->send();
        $envio = [true, 'E-mail enviado para ' . $para . '. Confira a caixa de entrada e o spam.'];
    } catch (Throwable $e) {
        $envio = [false, 'Falhou: ' . $e->getMessage()];
    }
    // esconde a senha (aparece em base64 no log do SMTP)
    $log = preg_replace('/^(.*CLIENT -> SERVER: )[A-Za-z0-9+\/=]{12,}\s*$/m', '$1[oculto]', $log);
}
$falhas = count(array_filter($checks, fn($c) => !$c[1]));
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Testes — Lindr</title>
<style>
body{font-family:Inter,Arial,sans-serif;background:#f4f1fb;color:#1b1533;margin:0;padding:24px}
main{max-width:820px;margin:auto}
h1{margin:0 0 4px}.mut{color:#6d6888}
.card{background:#fff;border:1px solid #e3def2;border-radius:16px;padding:18px;margin:16px 0}
table{width:100%;border-collapse:collapse}td{padding:8px 6px;border-bottom:1px solid #eee;vertical-align:top}
.ok{color:#14803c;font-weight:700}.no{color:#c0262d;font-weight:700}
input[type=email]{padding:10px;border:1px solid #cfc8e6;border-radius:10px;width:min(360px,100%)}
button{padding:10px 18px;border:0;border-radius:10px;background:#6b46d6;color:#fff;font-weight:700;cursor:pointer}
pre{background:#14102a;color:#d9d4f0;padding:12px;border-radius:10px;overflow:auto;font-size:12px;max-height:280px}
a{color:#5b34c9}.links a{display:inline-block;margin:4px 10px 4px 0}
</style>
</head>
<body>
<main>
<h1>Telinha de testes</h1>
<p class="mut">Só aparece em localhost. <?= $falhas === 0 ? '<span class="ok">Tudo certo no ambiente.</span>' : '<span class="no">' . $falhas . ' item(ns) precisam de atenção.</span>' ?></p>

<div class="card">
<h2>1. Ambiente</h2>
<table>
<?php foreach ($checks as [$nome, $ok, $det]): ?>
<tr><td class="<?= $ok ? 'ok' : 'no' ?>" style="width:28px"><?= $ok ? '✔' : '✖' ?></td><td><?= $h($nome) ?></td><td class="mut"><?= $h($det) ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="card">
<h2>2. Enviar e-mail de teste</h2>
<form method="post">
<input type="email" name="para" required placeholder="seu@email.com" value="<?= $h($_POST['para'] ?? '') ?>">
<button type="submit">Enviar teste</button>
</form>
<?php if ($envio): ?>
<p class="<?= $envio[0] ? 'ok' : 'no' ?>"><?= $h($envio[1]) ?></p>
<?php if ($log): ?><details <?= $envio[0] ? '' : 'open' ?>><summary>Detalhes da conversa com o servidor de e-mail</summary><pre><?= $h($log) ?></pre></details><?php endif; ?>
<?php endif; ?>
</div>

<div class="card links">
<h2>3. Telas para testar</h2>
<a href="cadastro.php">Cadastro</a><a href="login.php">Login</a><a href="senha_esqueci.php">Esqueci a senha</a>
<a href="descobrir.php">Descobrir</a><a href="eventos.php">Eventos</a><a href="comunidades.php">Comunidades</a>
<a href="perfil.php">Perfil</a><a href="dispositivos.php">Dispositivos confiáveis</a><a href="admin/index.php">Painel ADM</a>
<p class="mut">Roteiro com os 80 casos de teste: no documento "Lindr — Plano e roteiro de testes".</p>
</div>
</main>
</body>
</html>
