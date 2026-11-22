-- Lindr: migração da rodada 4 (estado/cidade por seleção + confirmação de e-mail).
-- Seguro para rodar mais de uma vez; não apaga dados.
USE lindr;

SET @db := DATABASE();

-- 1) usuarios.email_confirmado_em: quando a pessoa confirmou o e-mail (NULL = ainda não confirmou)
SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE usuarios ADD COLUMN email_confirmado_em DATETIME NULL AFTER verificado',
  'SELECT "email_confirmado_em já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='email_confirmado_em');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- contas que já existiam continuam entrando normalmente (só as novas precisam confirmar)
UPDATE usuarios SET email_confirmado_em = COALESCE(data_cadastro, NOW()) WHERE email_confirmado_em IS NULL;

-- 2) codigos_verificacao: guarda o HASH do código (64 caracteres) e conta as tentativas erradas
ALTER TABLE codigos_verificacao MODIFY codigo VARCHAR(64) NOT NULL;

SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE codigos_verificacao ADD COLUMN tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER usado',
  'SELECT "tentativas já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='codigos_verificacao' AND COLUMN_NAME='tentativas');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) grupos: cidade e estado do evento (escolhidos de uma lista)
SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE grupos ADD COLUMN cidade VARCHAR(100) NULL AFTER local_evento',
  'SELECT "grupos.cidade já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='grupos' AND COLUMN_NAME='cidade');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE grupos ADD COLUMN estado CHAR(2) NULL AFTER cidade',
  'SELECT "grupos.estado já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='grupos' AND COLUMN_NAME='estado');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Verificação em duas etapas por e-mail: aparelhos marcados como "confiar neste dispositivo" (30 dias).
-- Só o hash do token fica no banco; o token em si fica no cookie do navegador.
CREATE TABLE IF NOT EXISTS dispositivos_confiaveis (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso DATETIME NULL,
    expira_em DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_dispositivo_token (token_hash),
    KEY idx_dispositivo_usuario (usuario_id, expira_em),
    CONSTRAINT fk_dispositivo_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
