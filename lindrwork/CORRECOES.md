# Correções aplicadas (revisão de código)

## Bugs que quebravam o funcionamento
- **CSS:** 9 páginas novas (buscar, notificações, configurações, fotos, grupo, perfil público, termos, privacidade, regras) não carregavam o CSS base, então navbar/avatares/avisos apareciam sem estilo. Criado `css/base.css` (7 cópias idênticas foram removidas) e carregado em todas as páginas pelo `header.php`.
- **Aceitar/recusar pedido de amizade não funcionava:** `SELECT m.*, u.*` fazia o `id` do usuário sobrescrever o `id` do match. Corrigido com alias `match_id`.
- **Pessoa reaparecia em Descobrir depois de aceita:** ao aceitar, agora cria-se também a linha no sentido inverso; Descobrir exclui pares já conectados nos dois sentidos.
- **Matches duplicados na lista** (um match mútuo tem duas linhas): `mutual_matches()` agora deduplica.
- **`denunciar.php` não existia** (link quebrado em 2 telas) e **nada gravava em `bloqueios`**: criados `denunciar.php` e `bloquear.php` (bloquear/desbloquear), ligados a chat, perfil público, feed e perfil. Chat, feed, comentários e pedidos respeitam bloqueios.
- **Galeria de fotos:** a ordem era sempre 0 (`fetchColumn()` chamado duas vezes) e não havia foto principal. Corrigido e adicionado "Usar como principal".
- **Evento cancelado dava 404** ao abrir os detalhes; agora mostra aviso. Botão "Participar" de evento voltava para a página errada.
- **Instalação nova quebrava:** `database.sql` não tinha `discover_enabled`, `notificacoes_email` nem as tabelas novas. Incluídos. A migração virou idempotente.
- **Termos, Privacidade e Regras exigiam login**, mas eram linkados no rodapé do login/cadastro (redirecionava em loop). Agora são públicas.
- `footer.php` não fechava `</body></html>`; JS placeholders (8 arquivos idênticos, nunca carregados) removidos e `js/<pagina>.js` passou a ser carregado quando existe.

## Segurança e privacidade
- Entrar em **comunidade privada** burlando a tela (POST direto) era possível; **busca listava comunidades privadas**. Ambos corrigidos.
- Link de redefinição de senha usava `HTTP_HOST` (envenenamento de Host header): agora usa `LINDR_APP_URL`.
- Cookie de sessão com `HttpOnly`, `SameSite=Lax` e `Secure` (em HTTPS); logout agora é POST com CSRF.
- Política de senha unificada (cadastro exigia 6, troca/redefinição exigiam 10).
- Validação de URL de imagem (http/https, até 500 caracteres), limites de tamanho em textos, UF, data de nascimento, ids inexistentes (antes gerava erro 500 por chave estrangeira).
- Opção de visibilidade "Meus matches" existia no formulário mas não fazia nada; agora funciona no perfil público.
- Perfil pausado (`discover_enabled=0`) não bloqueia mais quem já é match.

## Coerência com o que o site promete
- Cadastro marcava todo mundo como `verificado=1` (e a landing dizia "Perfis verificados"). Agora cadastra com `verificado=0` e a landing diz "Apenas para maiores de 18 anos".
- O "+18" só era checado na edição de perfil. Agora o cadastro exige data de nascimento (≥18) e aceite de Termos/Privacidade.

## Não foi possível testar
Este ambiente não tinha PHP nem MySQL. A estrutura dos arquivos foi verificada (balanceamento de chaves/aspas), mas **faça um teste completo com 2 contas no XAMPP** antes de apresentar.

---

# Revisão sobre esta base (só correções, sem mudar o visual)

Esta versão é o ponto de partida. O visual (barra escura, faixa colorida lateral, painéis do login) **foi mantido**. Foram feitas apenas as correções abaixo.

## Navbar
- **Rolagem lateral:** a lista de links tinha `overflow-x:auto`, então Eventos, Avisos e Perfil ficavam escondidos mesmo em tela grande (a barra precisava de ~820px e só havia ~420px). Agora os links quebram para uma segunda linha quando não cabem. A partir de 1600px continua tudo em uma linha, igual ao original. Testado de 360px a 1920px: nenhum item fora da tela, sem rolagem horizontal.
- Links da barra apareciam sublinhados (faltava `text-decoration:none`).
- Botões com margens de 16px (feitas para links soltos) e preenchimento exagerado: ajustados para caber.
- No celular a barra não é mais fixa (ocupava um terço da tela) e ficou compacta.

## Login e cadastro
- **Botão "Criar nova conta" invisível** (texto branco em fundo branco: o `!important` do `.btn` em `social.css` vencia a cor do botão secundário).
- Painel escuro da esquerda "ilhado" por um padding herdado de `.pg`.
- Título com `line-height` herdado do corpo (1.55), deixando linhas muito afastadas.
- Modo escuro: os campos de texto ficavam com fundo branco fixo.
- Placeholder da senha cortado no celular; logo grande empurrava os botões da barra.

## Outros bugs
- **Página de comunidade/evento mostrava o nome do criador no lugar do nome do grupo** (colisão de colunas `nome` na consulta, mesmo erro que existia em `matches.php`).
- **Telas "Esqueci minha senha" e "Redefinir senha" não carregavam a navbar**, que é onde o aviso é impresso: a mensagem de sucesso/erro nunca aparecia.
- Janelas de criar comunidade/evento (`<dialog class="card">`) ficavam coladas à esquerda por causa da margem do `.card`.
- Painel admin: senha mínima de 6 caracteres (o resto do site exige 10, com letras e números).

## Contas de demonstração
`seed_demo.sql` (que substituiu o antigo `seed_usuarios.sql`) cria as contas e os dados de demonstração. Veja `LEIA-ME-DEMO.md`.

---

# Verificação em duas etapas (2FA)

Aplicativo autenticador (TOTP, RFC 6238): Google Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden etc. Não depende de SMS nem de e-mail e não usa bibliotecas externas.

## Como funciona
- **Ativar:** *Configurações → Verificação em duas etapas → Ativar*. A página mostra um QR Code (e a chave para digitar manualmente); a pessoa digita o primeiro código para confirmar. Em seguida recebe **8 códigos de backup** (aparecem uma única vez; dá para copiar, baixar ou imprimir).
- **Entrar:** após e-mail e senha corretos, o login pede o código de 6 dígitos (`verificacao_2fa.php`). A sessão **só é autenticada depois** do código. Sem acesso ao celular, vale um código de backup (uso único).
- **Gerenciar:** `dois_fatores.php`. Gerar novos códigos e desativar exigem **senha + código** do aplicativo.
- **Recuperação:** no painel admin, em *Usuários → Editar*, o botão "Remover verificação em duas etapas" libera quem perdeu o aparelho e os códigos (fica no log de auditoria).

## Segurança
- O segredo é guardado **criptografado** (AES-256-GCM). A chave vem de `LINDR_APP_KEY` ou do arquivo `storage/app.key`, criado automaticamente (pasta bloqueada ao navegador por `.htaccess`). **Faça backup de `storage/app.key`**: sem ele os segredos já gravados não abrem (o admin consegue resetar, mas a pessoa terá que ativar de novo).
- Códigos de backup: só o hash SHA-256 vai ao banco.
- **5 erros seguidos bloqueiam por 15 minutos** (controle no banco, não na sessão, então abrir outra aba não burla).
- Cada código do aplicativo só vale **uma vez** (guarda o último passo usado, com UPDATE condicional contra duas requisições simultâneas).
- Aceita ±30 s de diferença de relógio. A etapa pendente expira em 10 minutos.
- O QR Code é desenhado **no navegador** (`js/qrcode.js`, biblioteca MIT de Kazuhiko Arase, licença dentro do arquivo): a chave não é enviada a nenhum site externo.

## Como foi testado
- Algoritmo TOTP conferido com os 4 vetores oficiais da RFC 6238.
- QR Code gerado pela biblioteca e **lido de volta** (OpenCV) idêntico ao `otpauth://` esperado, também na tela real de configuração.
- Telas renderizadas em Chromium (desktop e celular) sem erros de JavaScript nem rolagem lateral.
- **Não rodei o PHP** (sem interpretador aqui): faça o teste de ponta a ponta no XAMPP: ativar, sair, entrar com código, entrar com backup, errar 5 vezes, desativar.

## Arquivos
Novos: `includes/totp.php`, `verificacao_2fa.php`, `dois_fatores.php`, `js/qrcode.js`, `js/dois_fatores.js`, `storage/.htaccess`.
Alterados: `login.php`, `configuracoes.php`, `admin/usuarios.php`, `database.sql`, `evolucao_migration.sql`, `css/social.css`, `.env.example`.

---

# Correção da navbar que "não funcionou" (causa raiz)

**Problema:** `index.css` (feed) e `login.css` continham **cópias antigas do CSS base** (66 e 76 regras repetidas), incluindo `.top{height:66px}` e `.nl{overflow-x:auto}`. Como carregam depois do `base.css`, anulavam a correção: no feed a barra ficava com altura fixa de 66px e os links "Comunidades, Eventos, Avisos, Perfil" vazavam para fora dela.

**Correção:**
- `index.css` e `login.css` agora só têm o que é específico de cada página (131→62 e 88→12 regras). Comentário no topo avisa para não repetir regras do `base.css`.
- Removidas de `index.css` três regras que ganhavam das regras de celular do `base.css` (`.top a{margin-right}`, `.nl a`, `.logo`, `.top .btn.sm`), deixando o feed idêntico às outras páginas.
- `header.php` agora anexa `?v=<data de modificação>` aos CSS, então o navegador não usa mais uma versão antiga em cache quando o CSS muda.

**Como foi testado:** navbar renderizada com a pilha de CSS **real de cada página** (feed = base+index+social; demais = base+social) em 10 larguras de 360px a 1920px: sem rolagem lateral, nenhum link fora da tela, nada vazando da barra, e altura idêntica entre o feed e as outras páginas. Regressão: estilo calculado de todos os elementos de `index.css`/`login.css` comparado antes/depois da limpeza; as únicas diferenças são o espaçamento do logo e o título do login (correções intencionais).

---

# Navegação em barra inferior (estilo app de celular)

Quando a tela tem menos de 1600px (notebooks, tablets e celulares), os 9 links do menu deixam de ir para uma segunda linha e viram uma **barra fixa na parte de baixo da tela**. O topo fica só com o logo, o nome e os botões (Painel ADM, Configurações, Sair), numa linha única e fixa. A partir de 1600px o menu continua no topo, em uma linha, como antes.

- Cada item tem **ícone + nome**. Em telas até 500px (celulares) aparecem só os ícones e o **nome do item ativo**, pois 9 nomes não cabem lado a lado. Os links têm `title` e `aria-label` para leitores de tela e dica ao passar o mouse.
- A barra é arredondada e flutuante (até 780px de largura, centralizada) em notebooks e tablets; ocupa a largura toda nos celulares. Respeita a área segura do iPhone (`safe-area-inset-bottom`).
- O conteúdo ganha espaço no final (`padding-bottom`) para não ficar escondido atrás da barra, e os avisos (toast) sobem para ficarem acima dela.
- Arquivos: `includes/navbar.php` (ícone + `<span>` com o nome), `css/base.css`, `css/index.css` (removidas regras antigas de celular que brigavam com a barra), `config/database.php` (ícones novos: home, search, bell, user).

## Bug extra encontrado
O `onclick` do logo no `navbar.php` estava sem a aspa de fechamento (`location.href='index.php"`), o que dava erro de JavaScript e o clique no logo não fazia nada. Corrigido.

## Teste
Navbar real do projeto (`navbar.php` convertido para HTML) com as pilhas de CSS reais (feed e demais páginas) em 18 larguras de 320px a 1920px (36 combinações): barra sempre dentro da tela, sem rolagem lateral, sem nomes cortados, topo sem vazar, e o fim do conteúdo livre da barra. Não rodei o PHP: confira no navegador (Ctrl+F5 na primeira vez).

---

# Auditoria final (fechamento do TCC)

## Como foi feita
- **145 consultas SQL** extraídas de todos os PHP e conferidas, tabela por tabela e coluna por coluna, contra o esquema do banco: nenhuma coluna ou tabela inexistente.
- Busca por chamadas a **funções inexistentes** e por **links/includes quebrados** (237 referências): nenhum problema real.
- Leitura manual de todas as páginas atrás de erros de lógica e de segurança.
- A carga de dados foi validada contra o esquema (enums, tamanhos de campo, chaves únicas e estrangeiras) e as **consultas reais das páginas** foram executadas sobre os dados (feed, Amizades, Descobrir, Chat, Buscar, Avisos, Comunidades, Eventos, Admin).

## Erros corrigidos
**Segurança**
- **`verificar.php` removido:** página órfã que abria a sessão **sem passar pelo 2FA** e permitia disparar e-mails de verificação para qualquer endereço.
- **Login sem limite de tentativas:** agora 8 erros por e-mail ou 30 por IP em 15 minutos bloqueiam novas tentativas (tabela `tentativas_login`).
- **Login revelava se o e-mail existia** (respondia mais rápido quando não existia): agora a verificação de senha roda sempre, com hash falso.
- **"Esqueci minha senha" sem limite:** máximo de 3 pedidos por hora por conta (impede encher a caixa de e-mail de alguém).
- **Arquivos sensíveis baixáveis pelo navegador** (`.sql` com hashes, `.md`, `composer.json`): bloqueados por `.htaccess`.
- **Cadastro com o mesmo e-mail em dois cliques simultâneos** dava erro 500: agora mostra "Este e-mail já está cadastrado".

**Funcionamento**
- "Esqueci minha senha" **dizia que enviou o e-mail mesmo sem SMTP**, impedindo a demonstração: em `localhost` sem SMTP o link aparece na tela.
- **Eventos:** os já passados apareciam antes dos próximos; agora ficam em ordem (próximos, encerrados, cancelados) e o botão "Participar" some nos encerrados.
- **Feed:** ao curtir ou comentar a página voltava ao topo; agora volta ao mesmo post.
- **Avisos:** o menu não mostrava quantos não lidos existiam; agora há um contador.
- **Moderação:** a tela de denúncias só mudava o status; agora é possível **remover a publicação** e **suspender o usuário** direto dela (com registro na auditoria).
- `valid_image_url()` rejeitava caminhos locais (`img/...`), então quem tinha avatar local não conseguia salvar o perfil.

## Dados de demonstração e 2FA
- `seed_demo.sql`: 34 contas, 67 posts, 393 curtidas, 97 comentários, 76 linhas de amizade (28 matches mútuos), 153 mensagens, 338 avisos, 10 comunidades, 7 eventos, 5 denúncias; **avatares e imagens em SVG locais** (`img/`).
- Conta **Aline Dias** (`seguranca@lindr.demo`) com 2FA já ativado, QR Code em `demo/` e 8 códigos de backup. O segredo é guardado cifrado com a chave `storage/app.key` de demonstração; o formato foi conferido (AES-256-GCM, mesmo layout do PHP) e o código TOTP gerado pelo algoritmo confere com os vetores da RFC 6238.

## Limitações conhecidas
- O PHP não foi executado neste ambiente (sem interpretador). A estrutura dos arquivos, as consultas SQL e o front-end foram testados; o teste de ponta a ponta no XAMPP continua sendo necessário.
- Sem paginação no feed (mostra os 50 mais recentes).
- Cidade/estado continuam texto livre no perfil.
