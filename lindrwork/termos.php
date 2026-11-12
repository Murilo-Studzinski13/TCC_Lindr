<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
ob_start();

?>
<h1>Termos de uso</h1><div class="card"><p><b>Modelo inicial — revisar antes do lançamento.</b></p><h2>Convivência</h2><p>O Lindr é um espaço para amizades, interesses e comunidades. Trate outras pessoas com respeito e não publique ameaças, assédio, discurso de ódio, conteúdo íntimo sem consentimento ou informações privadas de terceiros.</p><h2>Conta e idade</h2><p>Forneça informações verdadeiras, proteja sua senha e utilize a plataforma somente se cumprir a idade mínima informada no cadastro e os requisitos legais aplicáveis.</p><h2>Conteúdo</h2><p>Você é responsável pelo conteúdo que publica e deve ter direito de compartilhar imagens e textos. Conteúdos denunciados podem ser analisados e removidos conforme as regras da comunidade.</p><h2>Segurança</h2><p>Não tente acessar contas de outras pessoas, contornar bloqueios, automatizar abusos ou explorar vulnerabilidades. Use as ferramentas de denúncia para relatar problemas.</p><h2>Moderação</h2><p>O Lindr poderá limitar conteúdo ou contas que violem as regras, com revisão proporcional e canal de contato a ser publicado pelo responsável.</p></div>

<?php
$content=ob_get_clean();
$page='termos'; $title='Termos — Lindr'; require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap">
<?= $content ?>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>