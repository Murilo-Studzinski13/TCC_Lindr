<?php
// Configuração do envio de e-mails do Lindr.
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
