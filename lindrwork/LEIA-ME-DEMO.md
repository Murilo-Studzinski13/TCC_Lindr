# Lindr — dados de demonstração

Importe **`seed_demo.sql`** no phpMyAdmin (banco `lindr` selecionado, depois de rodar `evolucao_migration.sql` ou usar o `database.sql` novo). Pode importar de novo quando quiser: os dados de demonstração são apagados e recriados.

> ⚠️ São contas e senhas **de teste**, só para o TCC. Não publique o site assim: apague `seed_demo.sql` e a pasta `demo/`, e troque as senhas.

## Contas principais

| Papel | E-mail | Senha |
|---|---|---|
| **Administrador** (painel `admin/`) | `admin@lindr.com` | `Admin@Lindr2026` |
| **Usuária principal** (Duda, perfil mais completo) | `usuario@lindr.com` | `Usuario@2026` |
| **Conta com verificação em duas etapas** (Aline Dias) | `seguranca@lindr.demo` | `Seguranca@2026` + código do aplicativo |
| Conta suspensa por spam (aparece no admin) | `promo.seguidores@lindr.demo` | `Demo@Lindr2026` (não consegue entrar) |

## Os 30 usuários de demonstração

Todos usam a senha **`Demo@Lindr2026`**.

| Nome | E-mail |
|---|---|
| Lucas Ferreira | `lucas.ferreira@lindr.demo` |
| Camila Rocha | `camila.rocha@lindr.demo` |
| Rafael Souza (Rafa) | `rafael.souza@lindr.demo` |
| Beatriz Lima (Bia) | `beatriz.lima@lindr.demo` |
| Thiago Almeida | `thiago.almeida@lindr.demo` |
| Alice Moreira | `alice.moreira@lindr.demo` |
| Gabriel Santos (Gabi) | `gabriel.santos@lindr.demo` |
| Júlia Andrade | `julia.andrade@lindr.demo` |
| Matheus Oliveira (Theo) | `matheus.oliveira@lindr.demo` |
| Larissa Campos (Lari) | `larissa.campos@lindr.demo` |
| Pedro Henrique Costa (PH) | `pedro.costa@lindr.demo` |
| Isadora Martins (Isa) | `isadora.martins@lindr.demo` |
| Enzo Barbosa | `enzo.barbosa@lindr.demo` |
| Valentina Pires (Vale) | `valentina.pires@lindr.demo` |
| Ícaro Nascimento | `icaro.nascimento@lindr.demo` |
| Helena Duarte (Lena) | `helena.duarte@lindr.demo` |
| Caio Mendes | `caio.mendes@lindr.demo` |
| Mariana Teixeira (Mari) | `mariana.teixeira@lindr.demo` |
| Davi Carvalho | `davi.carvalho@lindr.demo` |
| Yasmin Farias | `yasmin.farias@lindr.demo` |
| Otávio Ribeiro | `otavio.ribeiro@lindr.demo` |
| Luna Albuquerque | `luna.albuquerque@lindr.demo` |
| Bruno Cavalcante | `bruno.cavalcante@lindr.demo` |
| Sofia Nunes (Sô) | `sofia.nunes@lindr.demo` |
| Gustavo Pereira (Guga) | `gustavo.pereira@lindr.demo` |
| Antônia Vieira (Toni) | `antonia.vieira@lindr.demo` |
| Henrique Gomes | `henrique.gomes@lindr.demo` |
| Letícia Barros (Lê) | `leticia.barros@lindr.demo` |
| Murilo Santana | `murilo.santana@lindr.demo` |
| Clara Bastos | `clara.bastos@lindr.demo` |

## O que existe nos dados

- **34 contas** (administrador, usuária principal, 30 usuários, 1 suspensa, 1 com 2FA), com identidades, orientações, pronomes, cidades de vários estados, bios e interesses diferentes, e avatares ilustrados.
- **67 publicações** (algumas com imagem), **393 curtidas** e **97 comentários**.
- **Amizades:** 28 pares com match mútuo, 14 pedidos pendentes, 4 recusados e 2 bloqueios.
- **153 mensagens** em conversas de verdade (as da Duda têm de 6 a 9 mensagens e uma não lida).
- **10 comunidades** (8 públicas e 2 privadas, uma delas só visível para membros) com posts, e **7 eventos** (próximos, um já encerrado e um cancelado) com participantes e comentários.
- **338 avisos** (curtidas, comentários, convites, matches, mensagens). A Duda tem avisos não lidos.
- **5 denúncias** e 3 registros de auditoria para o painel admin.

## Roteiro sugerido para a apresentação

**1. Como usuária (`usuario@lindr.com`)**
1. **Feed:** posts de várias pessoas, curtir e comentar. O contador de **Avisos** mostra os não lidos.
2. **Amizades:** 4 pedidos recebidos (aceite um e recuse outro) e 4 amigos com conversa.
3. **Chat:** abra a conversa com a Sofia (tem mensagem não lida) e responda.
4. **Descobrir:** curta alguém novo (ex.: Bia). Entrando como ela (`beatriz.lima@lindr.demo`) o aviso de convite aparece.
5. **Comunidades e Eventos:** entre em uma comunidade, publique, confirme presença em um evento e comente.
6. **Buscar:** pesquise "jogo", "livro" ou "Criciúma".
7. **Privacidade:** Perfil → mude a visibilidade da identidade; Perfil público de outra pessoa → Denunciar / Bloquear.

**2. Como administrador (`admin@lindr.com`)**
- *Painel administrativo* (menu do topo): painel geral, **Denúncias** (remover post, suspender usuário), Usuários, Grupos e Publicações. A conta suspensa por spam aparece como inativa.

**3. Verificação em duas etapas**
1. Instale um aplicativo autenticador no celular (Google Authenticator, Microsoft Authenticator, Authy...).
2. Adicione a conta escaneando o QR Code do arquivo **`demo/qrcode-2fa-aline-dias.png`** (ou digite a chave `LZOHI5H55RS744QSS43XEIWXFA5LLI4D`).
3. Em `login.php`, entre com `seguranca@lindr.demo` / `Seguranca@2026`: aparece a tela **"Digite o código"**. Digite o código de 6 dígitos do aplicativo.
4. **Sem o celular?** Use um código de backup (uso único): `rvc4y-shhev`, `gavsr-gnti2`, `cwjer-7g4ka`, `4qnyf-yl6ez`, `utmgl-hca6q`, `rakh7-yh7z3`, `z4vwd-dxs3j`, `s7zar-7w6ki`
5. Errar o código **5 vezes** bloqueia a conta por 15 minutos.
6. Para mostrar a ativação do zero: entre como a Duda → *Configurações → Verificação em duas etapas → Ativar*.

> **Relógio:** o código depende da hora do computador onde o site roda. Se os códigos nunca baterem, sincronize o relógio do Windows (Configurações → Hora e idioma → Sincronizar agora).
>
> **Chave do 2FA:** `storage/app.key` é a chave de **demonstração** que protege os segredos no banco. Ela vem junto para a conta Aline Dias funcionar. Em produção, apague esse arquivo (o sistema cria um novo) e use contas próprias.

## "Esqueci minha senha" sem e-mail configurado
Rodando em `localhost` e sem SMTP, a tela mostra o **link de redefinição na própria página**, para dar para demonstrar o fluxo.
