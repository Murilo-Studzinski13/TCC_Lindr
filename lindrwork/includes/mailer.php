<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$autoload = __DIR__ . '/../vendor/autoload.php';
$manualBase = __DIR__ . '/../vendor/PHPMailer/src/';

if (is_file($autoload)) {
    require_once $autoload;
} elseif (is_file($manualBase . 'Exception.php')) {
    require_once $manualBase . 'Exception.php';
    require_once $manualBase . 'PHPMailer.php';
    require_once $manualBase . 'SMTP.php';
} else {
    throw new RuntimeException('PHPMailer não está instalado. Coloque a pasta PHPMailer em vendor/PHPMailer/ ou instale com Composer.');
}

function enviar_codigo_verificacao(string $destinatario, string $nome, string $codigo): void
{
    $config = require __DIR__ . '/../config/mail.php';

    if (!$config['username'] || !$config['password'] || !$config['from_email']) {
        throw new RuntimeException('SMTP do Lindr não configurado. Preencha config/mail.php ou as variáveis LINDR_SMTP_* .');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->Port = $config['port'];

    if ($config['encryption'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($destinatario, $nome);
    $mail->isHTML(true);
    $mail->Subject = 'Seu código de verificação — Lindr';
    $mail->Body = '
        <div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:30px;color:#1b1533">
            <h1 style="margin-bottom:8px">Lindr 🏳️‍🌈</h1>
            <p>Olá, ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '!</p>
            <p>Use este código para verificar seu e-mail:</p>
            <div style="font-size:34px;font-weight:800;letter-spacing:8px;padding:18px 0">' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</div>
            <p>O código expira em <strong>15 minutos</strong>.</p>
            <p style="color:#6d6888">Se você não criou uma conta no Lindr, ignore esta mensagem.</p>
        </div>';
    $mail->AltBody = "Olá, {$nome}! Seu código de verificação do Lindr é {$codigo}. Ele expira em 15 minutos.";
    $mail->send();
}


function enviar_link_redefinicao(string $destinatario, string $nome, string $url): void
{
    $config = require __DIR__ . '/../config/mail.php';
    if (!$config['username'] || !$config['password'] || !$config['from_email']) {
        throw new RuntimeException('SMTP do Lindr não está configurado.');
    }
    $autoload = __DIR__ . '/../vendor/autoload.php';
    $manualBase = __DIR__ . '/../vendor/PHPMailer/src/';
    if (is_file($autoload)) { require_once $autoload; }
    elseif (is_file($manualBase . 'Exception.php')) { require_once $manualBase . 'Exception.php'; require_once $manualBase . 'PHPMailer.php'; require_once $manualBase . 'SMTP.php'; }
    else { throw new RuntimeException('PHPMailer não está instalado.'); }
    $mail = new PHPMailer(true); $mail->isSMTP(); $mail->Host=$config['host']; $mail->SMTPAuth=true; $mail->Username=$config['username']; $mail->Password=$config['password']; $mail->Port=$config['port'];
    $mail->SMTPSecure=$config['encryption']==='ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet='UTF-8'; $mail->setFrom($config['from_email'],$config['from_name']); $mail->addAddress($destinatario,$nome); $mail->isHTML(true);
    $safeUrl=htmlspecialchars($url,ENT_QUOTES,'UTF-8'); $safeName=htmlspecialchars($nome,ENT_QUOTES,'UTF-8');
    $mail->Subject='Redefinição de senha — Lindr'; $mail->Body='<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:30px;color:#1b1533"><h1>Lindr</h1><p>Olá, '.$safeName.'!</p><p>Recebemos um pedido para redefinir a senha da sua conta.</p><p><a href="'.$safeUrl.'" style="display:inline-block;background:#6b46d9;color:white;padding:12px 18px;border-radius:10px;text-decoration:none">Redefinir senha</a></p><p>O link expira em 30 minutos. Se você não pediu a redefinição, ignore esta mensagem.</p></div>'; $mail->AltBody='Redefina sua senha neste link: '.$url.' (expira em 30 minutos).'; $mail->send();
}
