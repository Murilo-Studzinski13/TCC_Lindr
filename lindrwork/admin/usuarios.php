<?php
declare(strict_types = 1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/locais.php';
$admin = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'toggle') {
            $id = (int)$_POST['id'];
            if ($id === (int)$admin['id']) throw new RuntimeException('Você não pode desativar sua própria conta.');
            $s = $pdo->prepare('UPDATE usuarios SET ativo=IF(ativo=1,0,1) WHERE id=?');
            $s->execute([$id]);
            admin_log('alterou status do usuário', 'usuario', $id);
            flash('Status do usuário atualizado.', 'success');
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            if ($id === (int)$admin['id']) throw new RuntimeException('Você não pode excluir sua própria conta.');
            $s = $pdo->prepare('DELETE FROM usuarios WHERE id=?');
            $s->execute([$id]);
            admin_log('excluiu usuário', 'usuario', $id);
            flash('Usuário excluído.', 'success');
        } elseif ($action === 'reset2fa') {
            $id = (int)$_POST['id'];
            $pdo->prepare('DELETE FROM dispositivos_confiaveis WHERE usuario_id=?')->execute([$id]);
            admin_log('removeu os dispositivos confiáveis do usuário', 'usuario', $id);
            flash('Dispositivos confiáveis removidos. A pessoa vai receber o código por e-mail no próximo login.', 'success');
        } elseif ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $nivel = $_POST['nivel_acesso'] ?? 'usuario';
            $ativo = isset($_POST['ativo'])?1:0;
            if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Nome e e-mail válidos são obrigatórios.');
            if (!in_array($nivel, ['usuario', 'admin'], true)) $nivel = 'usuario';
            // estado e cidade são escolhidos da lista (nunca digitados)
            $legacy = [];
            if ($id) {
                $old = $pdo->prepare('SELECT estado,cidade FROM usuarios WHERE id=?');
                $old->execute([$id]);
                $legacy = array_values($old->fetch() ?: []);
            }
            [$uf, $cidade, $locErr] = location_from_post('estado', 'cidade', false, $legacy);
            if ($locErr) throw new RuntimeException($locErr);
            if ($id) {
                $s = $pdo->prepare('UPDATE usuarios SET nome=?, nome_social=?, email=?, cidade=?, estado=?, bio=?, nivel_acesso=?, ativo=?, verificado=? WHERE id=?');
                $s->execute([
                    $nome,
                    trim($_POST['nome_social'] ?? '')?:null,
                    $email,
                    $cidade,
                    $uf,
                    trim($_POST['bio'] ?? '')?:null,
                    $nivel,
                    $ativo,
                    isset($_POST['verificado'])?1:0,
                    $id
                ]);
                admin_log('editou usuário', 'usuario', $id);
                flash('Usuário atualizado.', 'success');
            } else {
                $senha = $_POST['senha'] ?? '';
                if ($pe = password_error($senha)) throw new RuntimeException($pe);
                // conta criada pelo ADM já nasce com o e-mail confirmado
                $s = $pdo->prepare('INSERT INTO usuarios (nome,email,senha_hash,nivel_acesso,ativo,verificado,nome_social,cidade,estado,bio,email_confirmado_em) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
                $s->execute([
                    $nome,
                    $email,
                    password_hash($senha, PASSWORD_DEFAULT),
                    $nivel,
                    $ativo,
                    isset($_POST['verificado'])?1:0,
                    trim($_POST['nome_social'] ?? '')?:null,
                    $cidade,
                    $uf,
                    trim($_POST['bio'] ?? '')?:null
                ]);
                $id = (int)$pdo->lastInsertId();
                admin_log('cadastrou usuário', 'usuario', $id);
                flash('Usuário cadastrado.', 'success');
            }
        }
    } catch (PDOException $e) {
        flash($e->getCode() === '23000' ? 'Já existe uma conta com este e-mail.' : 'Não foi possível salvar. Tente novamente.', 'error');
    } catch (Throwable $e) {
        flash($e->getMessage(), 'error');
    }
    redirect('usuarios.php' . (!empty($_POST['id'])?'?edit=' . (int)$_POST['id']:''));
}
$editId = isset($_GET['edit'])?(int)$_GET['edit']:0;
$editing = null;
if ($editId) {
    $s = $pdo->prepare('SELECT * FROM usuarios WHERE id=?');
    $s->execute([$editId]);
    $editing = $s->fetch();
}
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$sql = 'SELECT * FROM usuarios WHERE 1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (nome LIKE ? OR nome_social LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
if ($status === 'ativo') $sql .= ' AND ativo=1';
elseif ($status === 'inativo') $sql .= ' AND ativo=0';
$sql .= ' ORDER BY id DESC LIMIT 200';
$s = $pdo->prepare($sql);
$s->execute($params);
$users = $s->fetchAll();
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Usuários — Lindr</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include __DIR__ . '/partials.php'; ?>
    <main class="admin-main">
        <div class="section-head">
            <div>
                <h1>Usuários</h1>
                <p class="muted">Cadastre, edite, ative, desative ou exclua contas.</p>
            </div>
            <a class="btn primary" href="usuarios.php?action=new">+ Novo usuário</a>
        </div>
        <?php if ($flash): ?>
            <div class="flash <?=$flash['type']?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <?php
        if ($editing || ($_GET['action'] ?? '') === 'new'):
            $u = $editing?:[
                'id' => 0,
                'nome' => '',
                'nome_social' => '',
                'email' => '',
                'cidade' => '',
                'estado' => '',
                'bio' => '',
                'nivel_acesso' => 'usuario',
                'ativo' => 1,
                'verificado' => 1
            ];
            ?>
            <section class="card form-card">
                <h2><?= $u['id']?'Editar usuário':'Cadastrar usuário' ?></h2>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e((string)$u['id'])?>">
                    <div class="form-grid">
                        <label>Nome<input name="nome" required value="<?=e($u['nome'])?>"></label>
                        <label>Nome social<input name="nome_social" value="<?=e($u['nome_social']??'')?>"></label>
                        <label>E-mail<input type="email" name="email" required value="<?=e($u['email'])?>"></label>
                        <?php if (!$u['id']): ?>
                            <label>Senha<input type="password" name="senha" required minlength="10" autocomplete="new-password"></label>
                        <?php endif; ?>
                        <?= location_fields((string)($u['estado'] ?? ''), (string)($u['cidade'] ?? ''), 'Não informado', '../', 'estado', 'cidade', true) ?>
                        <label>Nível<select name="nivel_acesso">
                            <option value="usuario" <?=$u['nivel_acesso']==='usuario'?'selected':''?>>Usuário</option>
                            <option value="admin" <?=$u['nivel_acesso']==='admin'?'selected':''?>>Administrador</option>
                            </select>
                        </label>
                        <label>Bio<textarea name="bio"><?=e($u['bio']??'')?></textarea></label>
                    </div>
                    <div class="checks">
                        <label><input type="checkbox" name="ativo" <?=$u['ativo']?'checked':''?>> Conta ativa</label>
                        <label><input type="checkbox" name="verificado" <?=$u['verificado']?'checked':''?>> E-mail verificado</label>
                    </div>
                    <button class="btn primary">Salvar</button> <a class="btn" href="usuarios.php">Cancelar</a>
                </form>
                <?php if ($u['id']): ?>
                    <form method="post" style="margin-top:14px" onsubmit="return confirm('Remover os dispositivos confiáveis deste usuário? No próximo login ele receberá o código por e-mail.');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reset2fa"><input type="hidden" name="id" value="<?=e((string)$u['id'])?>"><button class="btn">Remover dispositivos confiáveis (volta a pedir o código por e-mail)</button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <section class="card">
            <form class="filters"><input name="q" placeholder="Buscar por nome ou e-mail" value="<?=e($q)?>"><select name="status">
                <option value="">Todos</option>
                <option value="ativo" <?=$status==='ativo'?'selected':''?>>Ativos</option>
                <option value="inativo" <?=$status==='inativo'?'selected':''?>>Inativos</option>
                </select><button class="btn">Filtrar</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Acesso</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>#<?= $u['id'] ?></td>
                                <td><strong><?= e(display_name($u)) ?></strong><small><?= e($u['cidade'] ?? '') ?></small></td>
                                <td><?= e($u['email']) ?></td>
                                <td><span class="badge <?=$u['ativo']?'ok':'off'?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
                                <td><?= $u['nivel_acesso'] === 'admin'?'Administrador':'Usuário' ?></td>
                                <td><?= date('d/m/Y', strtotime($u['data_cadastro'])) ?></td>
                                <td class="actions"><a class="btn sm" href="usuarios.php?edit=<?=$u['id']?>">Editar</a>
                                    <form method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn sm"><?= $u['ativo']?'Desativar':'Ativar' ?></button>
                                    </form>
                                    <?php if ((int)$u['id'] !== (int)$admin['id']): ?>
                                        <form method="post" onsubmit="return confirm('Excluir este usuário? Esta ação não pode ser desfeita.');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$u['id']?>"><button class="btn sm danger">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
