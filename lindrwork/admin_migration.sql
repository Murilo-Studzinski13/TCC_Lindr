-- LINDR: atualização segura para bancos existentes (MySQL/MariaDB).
-- Não apaga usuários, publicações, mensagens, matches ou outros dados.
USE lindr;

SET @db := DATABASE();

SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE usuarios ADD COLUMN foto_url VARCHAR(500) NULL AFTER estado',
  'SELECT "foto_url já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='foto_url');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE usuarios ADD COLUMN nivel_acesso ENUM(\'usuario\',\'admin\') NOT NULL DEFAULT \'usuario\' AFTER ativo',
  'SELECT "nivel_acesso já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='usuarios' AND COLUMN_NAME='nivel_acesso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*)=0,
  'ALTER TABLE grupos ADD COLUMN imagem_url VARCHAR(500) NULL AFTER tema',
  'SELECT "grupos.imagem_url já existe"')
  FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='grupos' AND COLUMN_NAME='imagem_url');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS auditoria_admin (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(80) NOT NULL,
    entidade VARCHAR(80) NULL,
    entidade_id BIGINT UNSIGNED NULL,
    detalhes TEXT NULL,
    data_acao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_admin (admin_id, data_acao),
    KEY idx_auditoria_data (data_acao),
    CONSTRAINT fk_auditoria_admin FOREIGN KEY (admin_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Confira após executar: SHOW COLUMNS FROM usuarios; SHOW COLUMNS FROM grupos; SHOW TABLES LIKE 'auditoria_admin';
