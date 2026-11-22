<?php
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('descobrir.php');
    check_csrf();
}

function get_interests(): array {
    global $pdo;
    return $pdo->query('SELECT * FROM interesses WHERE ativo=1 ORDER BY nome')->fetchAll();
}

function is_blocked(int $id): bool {
    global $pdo;
    $u = user();
    if (!$u) return true;
    $s = $pdo->prepare('SELECT 1 FROM bloqueios WHERE usuario_id=? AND bloqueado_id=? LIMIT 1');
    $s->execute([$u['id'], $id]);
    return (bool)$s->fetchColumn();
}

function mutual_matches(): array {
    global $pdo;
    $u = user();
    if (!$u) return [];

    $sql = 'SELECT m.*,
                   CASE WHEN m.usuario1_id=? THEN m.usuario2_id ELSE m.usuario1_id END AS other_id
            FROM matches m
            WHERE m.status="match"
              AND (m.usuario1_id=? OR m.usuario2_id=?)
              AND NOT EXISTS (
                    SELECT 1 FROM bloqueios b
                    WHERE b.usuario_id=?
                      AND b.bloqueado_id=CASE WHEN m.usuario1_id=? THEN m.usuario2_id ELSE m.usuario1_id END
              )
            ORDER BY COALESCE(m.data_match,m.data_criacao) DESC';
    $s = $pdo->prepare($sql);
    $s->execute([$u['id'], $u['id'], $u['id'], $u['id'], $u['id']]);

    // Um match mútuo pode ter duas linhas (A→B e B→A): mantém só uma por pessoa.
    $unique = [];
    foreach ($s->fetchAll() as $row) {
        $unique[(int)$row['other_id']] ??= $row;
    }
    return array_values($unique);
}

/** Id da conversa (linha em matches com status "match") entre duas pessoas, ou null se não houver. */
function find_conversation(int $a, int $b): ?int {
    global $pdo;
    $s = $pdo->prepare('SELECT id FROM matches WHERE status="match" AND ((usuario1_id=? AND usuario2_id=?) OR (usuario1_id=? AND usuario2_id=?)) ORDER BY id LIMIT 1');
    $s->execute([$a, $b, $b, $a]);
    $id = $s->fetchColumn();
    return $id ? (int)$id : null;
}

function ensure_conversation(int $a, int $b): int {
    $id = find_conversation($a, $b);
    if (!$id) redirect('matches.php');
    return $id;
}

/** Registra uma notificação interna. */
function notify_user(int $userId, string $type, string $title, string $message = '', ?string $link = null, ?int $actorId = null): void {
    global $pdo;
    $allowed = ['curtida', 'comentario', 'mensagem', 'match', 'convite', 'sistema'];
    if (!in_array($type, $allowed, true) || $userId < 1) return;
    $stmt = $pdo->prepare('INSERT INTO notificacoes (usuario_id,ator_id,tipo,titulo,mensagem,link) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$userId, $actorId, $type, mb_substr($title, 0, 160), mb_substr($message, 0, 500), $link]);
}

/* ---------- Upload de fotos (galeria do perfil) ---------- */
const FOTO_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const FOTO_DIR_REL = 'uploads/fotos';

/**
 * Valida e salva uma foto enviada pelo formulário.
 * Confere o conteúdo de verdade (não confia na extensão nem no tipo informado pelo navegador),
 * aceita só JPG/PNG/WEBP, recodifica a imagem (remove EXIF/GPS e qualquer código escondido)
 * e salva com nome aleatório. Retorna [caminho_relativo|null, mensagem_de_erro|null].
 */
function save_uploaded_photo(?array $file): array {
    if (!$file || !isset($file['error'])) return [null, 'Escolha uma imagem para enviar.'];
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_NO_FILE: return [null, 'Escolha uma imagem para enviar.'];
        case UPLOAD_ERR_INI_SIZE: case UPLOAD_ERR_FORM_SIZE: return [null, 'A imagem é grande demais (máximo 5 MB).'];
        default: return [null, 'Não foi possível enviar a imagem. Tente de novo.'];
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) return [null, 'Envio inválido.'];
    if (($file['size'] ?? 0) > FOTO_MAX_BYTES || filesize($tmp) > FOTO_MAX_BYTES) return [null, 'A imagem é grande demais (máximo 5 MB).'];

    $info = @getimagesize($tmp);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) return [null, 'Formato não aceito. Envie uma imagem JPG, PNG ou WEBP.'];
    if ($info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000) return [null, 'Dimensões da imagem não aceitas (máximo 8000×8000 px).'];
    $ext = $types[$info[2]];

    $dir = __DIR__ . '/../' . FOTO_DIR_REL;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return [null, 'Não foi possível salvar a imagem (pasta uploads/fotos sem permissão de escrita).'];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    $saved = false;
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring((string)file_get_contents($tmp));
        if ($img) {
            // corrige a rotação de fotos de celular antes de remover o EXIF
            if ($ext === 'jpg' && function_exists('exif_read_data')) {
                $exif = @exif_read_data($tmp);
                $o = (int)($exif['Orientation'] ?? 1);
                $deg = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
                if ($deg) {
                    $rot = imagerotate($img, $deg, 0);
                    if ($rot) {
                        imagedestroy($img);
                        $img = $rot;
                    }
                }
            }
            if ($ext === 'png' || $ext === 'webp') {
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }
            if ($ext === 'jpg') $saved = imagejpeg($img, $dest, 85);
            elseif ($ext === 'png') $saved = imagepng($img, $dest, 6);
            elseif (function_exists('imagewebp')) $saved = imagewebp($img, $dest, 85);
            imagedestroy($img);
        }
    }
    if (!$saved) {
        // sem GD: salva o arquivo original (já validado como imagem real acima)
        $saved = move_uploaded_file($tmp, $dest);
    }
    if (!$saved) return [null, 'Não foi possível salvar a imagem.'];
    @chmod($dest, 0644);
    return [FOTO_DIR_REL . '/' . $name, null];
}

/** Apaga do disco uma foto enviada por upload (ignora URLs externas e imagens do site). */
function delete_photo_file(?string $url): void {
    if (!$url || !preg_match('#^uploads/fotos/[a-f0-9]{32}\.(?:png|jpe?g|webp)$#', $url)) return;
    $f = __DIR__ . '/../' . $url;
    if (is_file($f)) @unlink($f);
}
