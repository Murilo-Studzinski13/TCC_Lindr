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
