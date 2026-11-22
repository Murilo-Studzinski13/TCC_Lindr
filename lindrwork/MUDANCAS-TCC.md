# Mudanças desta versão

## Antes de testar (1 passo no banco)
Rode `evidencias_migration.sql` no phpMyAdmin (cria a tabela das provas de denúncia). É seguro rodar mais de uma vez.
Dê permissão de escrita à pasta `uploads/` (e `storage/`).

## 1. Chat abre mesmo sem conversas
- `chat.php` agora sempre abre: lista de conversas à esquerda e, se não houver nenhuma, a mensagem "Você ainda não tem conversas" com atalhos para Descobrir e Amizades.
- Mostra última mensagem e contador de não lidas.

## 2. Fotos no perfil + envio por arquivo
- `perfil.php` mostra a galeria do usuário (com a marca "Principal") e tem o botão **Adicionar foto do meu aparelho**.
- `fotos.php` aceita upload de JPG/PNG/WEBP até 5 MB (o link por URL continua como opção).
- Segurança: confere o conteúdo real da imagem, recodifica (remove EXIF/GPS), nome aleatório, pasta `uploads/` que não executa código. O PHP precisa de `upload_max_filesize >= 5M`.
- A primeira foto vira a principal; ao remover a principal, a próxima assume; o arquivo é apagado do disco.

## 3. ADM não vê conversas, mas vê a prova da denúncia
- Nenhuma tela do ADM lê conversas.
- Ao denunciar (principalmente pelo botão do chat), o usuário anexa as **últimas 30 mensagens** como prova (`denuncia_evidencias`).
- Nova tela `admin/denuncia.php` ("Ver o que aconteceu"): resumo, publicação denunciada e a conversa anexada, com quem disse o quê e quando. Cada visualização fica no log de auditoria.

## 4. Verificação em duas etapas logo no login (obrigatória)
- Todo login (e todo cadastro novo) passa pela 2ª etapa **antes** de entrar no site.
- Quem ainda não tem autenticador configura na hora (QR Code), recebe os códigos de backup e só então entra.
- Não dá mais para desativar o 2FA em Configurações. Para testes locais: `LINDR_2FA_OBRIGATORIO=0`.
- Contas da demo (seed) vão configurar o autenticador no primeiro login.

## Rodada 2
- **Feed:** o formulário de publicar agora tem **foto do aparelho** (arquivo), igual ao perfil; dá para postar só a foto. Cada publicação própria tem botão **Excluir**.
- **Perfil:** nova seção **Minhas publicações com foto** (com exclusão). Outras pessoas veem essa seção em `perfil_publico.php`.
- **ADM / "Ver o que aconteceu":** corrigido (a consulta usava uma coluna que não existe, `data_criacao`; o certo é `data_publicacao`). Denúncias antigas não têm conversa anexada; só as feitas depois da migração.
- **2FA:** mantido o aplicativo autenticador (QR Code) com códigos de backup.

## Rodada 3 — Código organizado para o GitHub
- Todos os `.php` e `.css` foram reformatados: uma instrução por linha, indentação de 4 espaços, HTML quebrado em linhas, listas e consultas longas em várias linhas, espaços em torno de operadores.
- Conferido automaticamente: o código é idêntico ao anterior, mudaram só espaços e quebras de linha.
- Adicionado `.gitignore` (não sobe `.env` nem as fotos dos usuários).

## Rodada 4 — Listas de escolha, menu fixo e e-mail confirmado

### Antes de testar
Rode `melhorias_migration.sql` no phpMyAdmin (seguro rodar mais de uma vez). Ele cria `usuarios.email_confirmado_em`, passa `codigos_verificacao.codigo` para guardar o hash, adiciona `tentativas` e cria `cidade`/`estado` em `grupos` (eventos). Contas que já existiam continuam entrando normalmente.
Para ter **todas** as cidades do IBGE (hoje há uma lista resumida embutida), rode uma vez com internet: `php tools/gerar_municipios.php` (cria `includes/municipios.json`).

### 1. Estado e cidade por escolha (nada digitado)
- Novo `includes/locais.php`: 27 estados + cidades; Estado e Cidade viram listas ligadas (ao escolher o estado, as cidades carregam via `cidades.php`).
- O servidor **revalida** no POST: só aceita estado e cidade que estejam na lista, com a grafia oficial. Valor forjado é recusado.
- Aplicado em: cadastro (agora pede local), perfil, painel ADM (usuários), filtro do Descobrir (antes era texto livre "Cidade ou UF") e criação de eventos (estado/cidade escolhidos + ponto de encontro digitado).
- Contas antigas com cidade fora da lista não perdem o dado: o valor antigo continua aparecendo como opção.
- Também viraram lista: identidade de gênero, orientação e pronomes (`includes/opcoes.php`), tema da comunidade (usa a tabela `interesses`) e interesse do filtro Descobrir.

### 2. Menu fixo embaixo
- A barra de links agora fica fixa na parte de baixo em **qualquer** tamanho de tela (antes só abaixo de 1600px).

### 3. E-mail precisa ser confirmado (menos o ADM)
- Cadastro e login de conta não confirmada levam para `confirmar_email.php`: código de 6 dígitos enviado ao e-mail, válido por 15 min.
- Só o hash do código fica no banco; 5 erros invalidam o código; 1 reenvio por minuto e no máximo 5 por hora.
- O cadastro também confere se o domínio do e-mail existe (pega `gmial.com`).
- Administradores são dispensados; contas criadas pelo ADM já nascem confirmadas.
- Cadastro nunca confirmado (ex.: e-mail digitado errado) pode ser refeito com o mesmo e-mail.
- Em localhost sem SMTP o código aparece na tela (modo de demonstração). Em produção configure o SMTP e o PHPMailer.
- Ordem do login: senha → confirmação de e-mail (se faltar) → verificação em duas etapas.

### 4. Pequenos ajustes
- Data de nascimento só aceita quem tem 18+ (campo já limita a data).
- Evento só pode ser criado com data futura (campo e servidor).
- ADM: senha mínima do formulário era 6 e o servidor exigia 10; agora é 10 nos dois. E-mail duplicado mostra mensagem clara em vez de erro técnico, e o e-mail é salvo em minúsculas.

### 5. Verificação em duas etapas por e-mail (substitui o aplicativo autenticador)
- Depois da senha, em **aparelho novo** o site envia um código de 6 dígitos ao e-mail. Marcando *Confiar neste dispositivo*, o navegador fica cadastrado por 30 dias (cookie + só o hash no banco, tabela `dispositivos_confiaveis`) e não pede o código de novo, como na conta do Google.
- O mesmo código confirma o e-mail no primeiro acesso (no cadastro, a pessoa só entra depois do código). O administrador é dispensado só da *confirmação do e-mail*, mas também usa as duas etapas.
- Nova tela *Configurações → Dispositivos confiáveis* (listar, remover um ou todos). Trocar ou redefinir a senha remove todos os aparelhos confiáveis. No ADM há o botão de remover os dispositivos de um usuário.
- Removidos: `verificacao_2fa.php`, `dois_fatores.php`, `includes/totp.php`, QR Code e códigos de backup (as colunas antigas `totp_*` ficam no banco sem uso). Rode `melhorias_migration.sql` de novo para criar a tabela.
- Contas de demonstração (`@lindr.demo`, `@lindr.com`): em localhost o código aparece na tela.

### 6. Telinha de testes
- `teste.php` (só em localhost): confere PHP, extensões, banco, se a migração foi rodada, PHPMailer, SMTP e lista de cidades, e envia um e-mail de teste mostrando o diálogo com o servidor (senha escondida). Apague o arquivo depois da apresentação.
- Login: removido o botão branco "Criar nova conta" (fica só o link "Criar agora").

### 7. Painel ADM no celular e no tablet (falha CT79 do checklist)
- Em telas de até 900px o menu do ADM agora fica fixo na parte de baixo, como no site (5 botões em grade), com espaço no fim da página para não cobrir o conteúdo. Tabelas continuam rolando para o lado dentro do próprio quadro.
