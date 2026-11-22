<?php
<<<<<<< HEAD
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/app_helpers.php';
ob_start();
?>
<h1>Regras da comunidade</h1>
<div class="card">
    <p>Queremos que o Lindr seja um espaço acolhedor, especialmente para pessoas LGBTQIA+. Estas regras devem ser aplicadas de forma consistente.</p>
    <h2>Respeito e consentimento</h2>
    <ul>
        <li>Respeite nomes, pronomes e limites pessoais.</li>
        <li>Não assedie, ameace, persiga ou pressione outras pessoas.</li>
        <li>Não exponha a identidade LGBTQIA+ de alguém sem consentimento.</li>
    </ul>
    <h2>Conteúdo proibido</h2>
    <ul>
        <li>Discurso de ódio, discriminação ou incentivo à violência.</li>
        <li>Fotos íntimas sem consentimento ou exploração sexual.</li>
        <li>Golpes, spam, impersonação ou perfis falsos usados para prejudicar pessoas.</li>
        <li>Divulgação de endereço, telefone ou dados privados de terceiros sem autorização.</li>
    </ul>
    <h2>Como denunciar</h2>
    <p>Use o botão de denúncia no perfil, na publicação ou na conversa. Bloqueie a pessoa se precisar interromper o contato. Denúncias devem ser avaliadas por moderadores autorizados.</p>
    <h2>Moderação</h2>
    <p>Medidas possíveis incluem remoção de conteúdo, restrição temporária e desativação da conta em casos graves ou repetidos. O procedimento de revisão deve ser definido antes do lançamento público.</p>
</div>
<?php
$content = ob_get_clean();
$page = 'regras';
$title = 'Regras — Lindr';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<div class="pg">
    <main class="wrap"> <?= $content ?> </main>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
=======
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/app_helpers.php';
ob_start();

?>
<h1>Regras da comunidade</h1><div class="card"><p>Queremos que o Lindr seja um espaço acolhedor, especialmente para pessoas LGBTQIA+. Estas regras devem ser aplicadas de forma consistente.</p><h2>Respeito e consentimento</h2><ul><li>Respeite nomes, pronomes e limites pessoais.</li><li>Não assedie, ameace, persiga ou pressione outras pessoas.</li><li>Não exponha a identidade LGBTQIA+ de alguém sem consentimento.</li></ul><h2>Conteúdo proibido</h2><ul><li>Discurso de ódio, discriminação ou incentivo à violência.</li><li>Fotos íntimas sem consentimento ou exploração sexual.</li><li>Golpes, spam, impersonação ou perfis falsos usados para prejudicar pessoas.</li><li>Divulgação de endereço, telefone ou dados privados de terceiros sem autorização.</li></ul><h2>Como denunciar</h2><p>Use o botão de denúncia no perfil, na publicação ou na conversa. Bloqueie a pessoa se precisar interromper o contato. Denúncias devem ser avaliadas por moderadores autorizados.</p><h2>Moderação</h2><p>Medidas possíveis incluem remoção de conteúdo, restrição temporária e desativação da conta em casos graves ou repetidos. O procedimento de revisão deve ser definido antes do lançamento público.</p></div>

<?php
$content=ob_get_clean();
$page='regras'; $title='Regras — Lindr'; require __DIR__.'/includes/header.php'; require __DIR__.'/includes/navbar.php'; ?>
<div class="pg"><main class="wrap">
<?= $content ?>
</main></div><?php require __DIR__.'/includes/footer.php'; ?>
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
