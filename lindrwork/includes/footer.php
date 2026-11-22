<footer style="padding:28px 16px;text-align:center;color:#6d6888;font-size:13px">
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap"><a href="regras.php">Regras da comunidade</a><a href="privacidade.php">Privacidade</a><a href="termos.php">Termos de uso</a><a href="configuracoes.php">Configurações</a></div>
    <p>© <?= date('Y') ?> Lindr · Conexões com respeito.</p>
</footer>
<?php if (!empty($page) && is_file(__DIR__ . '/../js/' . $page . '.js')): ?>
    <script src="js/<?=e($page)?>.js"></script>
<?php endif; ?>
</body>
</html>
