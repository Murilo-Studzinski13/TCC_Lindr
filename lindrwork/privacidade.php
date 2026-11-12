<?php
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
ob_start();

?>
<h1>Política de privacidade</h1><div class="card"><p><b>Última revisão:</b> 13 de outubro de 2026</p><p>Esta página é um modelo inicial e precisa ser revisada juridicamente antes do lançamento público do Lindr.</p><h2>Dados tratados</h2><p>O Lindr pode tratar dados de cadastro e autenticação, informações de perfil, fotos, publicações, mensagens, interações, denúncias e registros técnicos necessários à segurança.</p><h2>Dados sensíveis e visibilidade</h2><p>Informações sobre orientação sexual e identidade de gênero devem receber proteção especial. Preencha apenas o que desejar e confira as configurações de visibilidade. Não publique endereço residencial nem localização precisa.</p><h2>Uso dos dados</h2><p>Os dados são utilizados para fornecer as funcionalidades sociais, manter a segurança, moderar conteúdo e responder a solicitações. O acesso administrativo deve ser limitado a pessoas autorizadas.</p><h2>Compartilhamento e retenção</h2><p>O projeto deve documentar fornecedores, prazos de retenção, bases legais, medidas de segurança e procedimentos para atendimento dos direitos dos titulares antes de operar publicamente.</p><h2>Seus direitos</h2><p>Você pode solicitar informações, correção ou exclusão pelos canais oficiais que serão divulgados pelo responsável pelo Lindr. Este modelo não substitui o aviso de privacidade final nem a análise de conformidade com a LGPD.</p></div>

<?php
$content=ob_get_clean();
$page='privacidade'; $title='Privacidade — Lindr'; require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap">
<?= $content ?>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>