<?php
// Configuração do envio de e-mails do Lindr.
<<<<<<< HEAD
// Os dados reais (usuário e senha de app) ficam em config/mail.local.php, que NÃO vai para o GitHub.
// Também dá para usar as variáveis de ambiente LINDR_SMTP_*.

$config = [
    'host' => getenv('LINDR_SMTP_HOST') ?: 'smtp.gmail.com',
    'port' => (int)(getenv('LINDR_SMTP_PORT') ?: 587),
    'username' => getenv('LINDR_SMTP_USER') ?: '',
    'password' => getenv('LINDR_SMTP_PASS') ?: '',
    'encryption' => getenv('LINDR_SMTP_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('LINDR_MAIL_FROM') ?: '',
    'from_name' => getenv('LINDR_MAIL_NAME') ?: 'Lindr',
];

$local = __DIR__ . '/mail.local.php';
if (is_file($local)) {
    $config = array_merge($config, (array)require $local);
}
return $config;
=======
// Preencha com os dados da sua conta SMTP.

return [
    'host'       => getenv('LINDR_SMTP_HOST') ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('LINDR_SMTP_PORT') ?: 587),
    'username'   => getenv('LINDR_SMTP_USER') ?: 'Lindr',
    'password'   => getenv('LINDR_SMTP_PASS') ?: 'mkycqwwcglijijjh',
    'encryption' => getenv('LINDR_SMTP_ENCRYPTION') ?: 'tls',
    'from_email' => getenv('LINDR_MAIL_FROM') ?: 'reinounidoruim3.0@gmail.com',
    'from_name'  => getenv('LINDR_MAIL_NAME') ?: 'Lindr',
];
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
