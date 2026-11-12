-- Lindr: novos recursos sociais. Faça backup antes de executar.
-- Pode ser executado mais de uma vez: só adiciona o que ainda não existe.
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='discover_enabled');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN discover_enabled TINYINT(1) NOT NULL DEFAULT 1','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='notificacoes_email');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN notificacoes_email TINYINT(1) NOT NULL DEFAULT 0','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

-- Verificação em duas etapas (2FA)
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='totp_secret');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN totp_secret VARCHAR(255) NULL','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='totp_ativo');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN totp_ativo TINYINT(1) NOT NULL DEFAULT 0','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='totp_ultimo_passo');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN totp_ultimo_passo BIGINT NULL','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='totp_falhas');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN totp_falhas TINYINT UNSIGNED NOT NULL DEFAULT 0','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='usuarios' AND COLUMN_NAME='totp_bloqueado_ate');
SET @q := IF(@c=0,'ALTER TABLE usuarios ADD COLUMN totp_bloqueado_ate DATETIME NULL','SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS notificacoes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 usuario_id BIGINT UNSIGNED NOT NULL,
 ator_id BIGINT UNSIGNED NULL,
 tipo ENUM('curtida','comentario','mensagem','match','convite','sistema') NOT NULL DEFAULT 'sistema',
 titulo VARCHAR(160) NOT NULL,
 mensagem VARCHAR(500) NULL,
 link VARCHAR(500) NULL,
 lida TINYINT(1) NOT NULL DEFAULT 0,
 criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_notif_user (usuario_id,lida,criada_em),
 CONSTRAINT fk_notif_user FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
 CONSTRAINT fk_notif_actor FOREIGN KEY(ator_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS publicacoes_grupos (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, grupo_id BIGINT UNSIGNED NOT NULL, usuario_id BIGINT UNSIGNED NOT NULL, conteudo TEXT NOT NULL, imagem_url VARCHAR(500) NULL, criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_pg_grupo(grupo_id,criada_em),
 CONSTRAINT fk_pg_grupo FOREIGN KEY(grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
 CONSTRAINT fk_pg_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS comentarios_eventos (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, grupo_id BIGINT UNSIGNED NOT NULL, usuario_id BIGINT UNSIGNED NOT NULL, conteudo TEXT NOT NULL, criada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_ce_grupo(grupo_id,criada_em),
 CONSTRAINT fk_ce_grupo FOREIGN KEY(grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
 CONSTRAINT fk_ce_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tokens_redefinicao_senha (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, usuario_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, expira_em DATETIME NOT NULL, usado TINYINT(1) NOT NULL DEFAULT 0, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_reset_hash(token_hash), KEY idx_reset_usuario(usuario_id,usado,expira_em),
 CONSTRAINT fk_reset_usuario FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS backup_codes_2fa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    codigo_hash CHAR(64) NOT NULL,
    usado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_backup_usuario (usuario_id, usado_em),
    CONSTRAINT fk_backup_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Limite de tentativas de login (segurança)
-- ============================================================
CREATE TABLE IF NOT EXISTS tentativas_login (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tl_email (email, criado_em),
    KEY idx_tl_ip (ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
