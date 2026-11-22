# Lindr — pacote de evolução social

## Novas páginas
- `buscar.php`: busca por pessoas, publicações e comunidades.
- `notificacoes.php`: histórico de notificações internas e marcar como lida.
- `configuracoes.php`: pausar descoberta, preferências, alterar senha e desativar conta.
- `fotos.php`: gerenciar até 8 URLs de fotos de perfil.
- `perfil_publico.php`: visualização pública de perfil com respeito à visibilidade de identidade.
- `grupo.php`: detalhe de comunidade/evento, publicações em comunidades e comentários em eventos.
- `senha_esqueci.php` e `senha_redefinir.php`: fluxo de redefinição de senha por link de uso único e validade de 30 minutos.
- `privacidade.php`, `termos.php`, `regras.php`: páginas iniciais de políticas/regras para revisar antes do lançamento.

## Instalação
**Banco novo:** importe `database.sql` (já inclui todas as tabelas e colunas).

**Banco existente:**
1. Faça backup do banco no phpMyAdmin (Exportar).
2. Selecione o banco do Lindr e execute `evolucao_migration.sql`. Ele é idempotente: só cria o que ainda não existe, então pode rodar mais de uma vez.
3. Copie `.env.example` para as variáveis de ambiente do servidor e defina `LINDR_APP_URL` (usada nos links de redefinição de senha) e o SMTP.
4. Rode `composer install` para instalar o PHPMailer (a pasta `vendor/` não vai no pacote).

## Notas importantes
- As novas páginas dependem de `evolucao_migration.sql`; não as use antes de executar a migração.
- A galeria de fotos usa URLs de imagens nesta versão; upload de arquivos requer validação de MIME, tamanho, extensão e armazenamento seguro no servidor.
- `privacidade.php`, `termos.php` e `regras.php` são modelos iniciais, não aconselhamento jurídico. Devem ser revisados antes de abrir o site ao público.
- O fluxo de redefinição de senha depende do PHPMailer instalado e SMTP configurado.
- Faça testes com duas contas, especialmente descoberta, bloqueio, chat, notificações e privacidade.
- Nenhum teste real contra o MySQL do seu XAMPP foi possível neste ambiente; a sintaxe dos arquivos PHP foi verificada.

## Contas e dados de demonstração
Importe **`seed_demo.sql`** (veja o passo a passo, as senhas e o roteiro de apresentação em **`LEIA-ME-DEMO.md`**). Ele cria 34 contas com publicações, curtidas, comentários, amizades, conversas, comunidades, eventos, denúncias e uma conta com verificação em duas etapas já ativada.

<<<<<<< HEAD
## Verificação em duas etapas (por e-mail)
1. Rode **`melhorias_migration.sql`** no phpMyAdmin (cria a tabela `dispositivos_confiaveis`; seguro rodar mais de uma vez). Em banco novo, `database.sql` já inclui tudo.
2. Configure o SMTP em `config/mail.local.php` e instale o PHPMailer em `vendor/PHPMailer/src/` (já vem no pacote).
3. Teste: entre com um usuário em um navegador novo > digite o código recebido no e-mail > marque *Confiar neste dispositivo* > saia e entre de novo: não pede mais o código.
=======
## Verificação em duas etapas (2FA)
1. **Rode `evolucao_migration.sql`** de novo no phpMyAdmin (cria as colunas `totp_*` em `usuarios` e a tabela `backup_codes_2fa`; é seguro rodar mais de uma vez). Em banco novo, `database.sql` já inclui tudo.
2. A pasta **`storage/` precisa de permissão de escrita** (o PHP cria `storage/app.key`, a chave que protege os segredos). Alternativa: defina `LINDR_APP_KEY` no ambiente (veja `.env.example`).
3. Extensão `openssl` do PHP ativa (já vem ativa no XAMPP).
4. Teste: entre com um usuário > *Configurações* > *Ativar verificação em duas etapas*.
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373

## Requisitos
- **PHP 8.1 ou superior** (o código usa o tipo de retorno `never`; o XAMPP atual já vem com 8.2+).
- Extensões: `pdo_mysql`, `mbstring`, `openssl` (2FA), `curl` ou `allow_url_fopen` (opcional, e-mail).
- MySQL 8+ ou MariaDB 10.6+.
<<<<<<< HEAD

## Segurança ao publicar o site
- Apague `seed_demo.sql`, `LEIA-ME-DEMO.md` e a pasta `demo/`, e troque as senhas das contas de teste.
=======
- Relógio do computador correto (os códigos do 2FA dependem da hora).

## Segurança ao publicar o site
- Apague `seed_demo.sql`, `LEIA-ME-DEMO.md` e a pasta `demo/`, e troque as senhas das contas de teste.
- Apague `storage/app.key` (o sistema cria uma chave nova e privada; quem tinha 2FA da demonstração precisa ativar de novo).
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
- O `.htaccess` da raiz bloqueia o download de `.sql`, `.md`, `.json`, `.env` e chaves. Ele só funciona se o Apache permitir `AllowOverride` (no XAMPP já vem assim).
