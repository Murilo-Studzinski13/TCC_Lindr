-- ============================================================
-- LINDR - BANCO DE DADOS COMPLETO
-- MySQL 8+ / MariaDB 10.6+
-- ============================================================

CREATE DATABASE IF NOT EXISTS lindr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lindr;

-- ATENÇÃO: este arquivo é para instalação inicial em um banco vazio.
-- Ele não deve ser executado sobre um banco com dados existentes.

-- ============================================================
-- USUARIOS
-- ============================================================
CREATE TABLE usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    nome_social VARCHAR(100) NULL,
    email VARCHAR(255) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    data_nascimento DATE NULL,
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
    foto_url VARCHAR(500) NULL,
    bio TEXT NULL,
    identidade_genero VARCHAR(50) NULL,
    orientacao VARCHAR(50) NULL,
    pronomes VARCHAR(30) NULL,
    visibilidade_identidade ENUM('privado','amigos','publico') NOT NULL DEFAULT 'privado',
    procurando ENUM('amizade','grupo','eventos','conversar') NOT NULL DEFAULT 'amizade',
    interesses TEXT NULL,
    preferencias TEXT NULL,
    verificado TINYINT(1) NOT NULL DEFAULT 0,
<<<<<<< HEAD
    email_confirmado_em DATETIME NULL,
=======
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    discover_enabled TINYINT(1) NOT NULL DEFAULT 1,
    notificacoes_email TINYINT(1) NOT NULL DEFAULT 0,
    totp_secret VARCHAR(255) NULL,
    totp_ativo TINYINT(1) NOT NULL DEFAULT 0,
    totp_ultimo_passo BIGINT NULL,
    totp_falhas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    totp_bloqueado_ate DATETIME NULL,
    nivel_acesso ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_local (estado, cidade, ativo),
    KEY idx_usuarios_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FOTOS DO PERFIL
-- Uma foto pode ser marcada como principal.
-- ============================================================
CREATE TABLE fotos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    foto_url VARCHAR(500) NOT NULL,
    principal TINYINT(1) NOT NULL DEFAULT 0,
    ordem INT NOT NULL DEFAULT 0,
    data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_fotos_usuario (usuario_id, principal, ordem),
    CONSTRAINT fk_fotos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CODIGOS DE VERIFICACAO
-- ============================================================
CREATE TABLE codigos_verificacao (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
<<<<<<< HEAD
    codigo VARCHAR(64) NOT NULL, -- hash SHA-256 do código de 6 dígitos
    tipo ENUM('email','senha') NOT NULL DEFAULT 'email',
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
=======
    codigo CHAR(6) NOT NULL,
    tipo ENUM('email','senha') NOT NULL DEFAULT 'email',
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_codigo_usuario (usuario_id, tipo, usado),
    KEY idx_codigo_expira (expira_em),
    CONSTRAINT fk_codigo_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
<<<<<<< HEAD
-- DISPOSITIVOS CONFIAVEIS (verificação em duas etapas por e-mail: "confiar neste dispositivo")
-- ============================================================
CREATE TABLE dispositivos_confiaveis (
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

-- ============================================================
=======
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
-- INTERESSES
-- ============================================================
CREATE TABLE interesses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_interesse_nome (nome),
    UNIQUE KEY uq_interesse_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_interesses (
    usuario_id BIGINT UNSIGNED NOT NULL,
    interesse_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (usuario_id, interesse_id),
    CONSTRAINT fk_ui_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ui_interesse
        FOREIGN KEY (interesse_id) REFERENCES interesses(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MATCHES
-- ============================================================
CREATE TABLE matches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario1_id BIGINT UNSIGNED NOT NULL,
    usuario2_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pendente','match','rejeitado','bloqueado') NOT NULL DEFAULT 'pendente',
    bloqueado_por BIGINT UNSIGNED NULL,
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_match DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_match_direcao (usuario1_id, usuario2_id),
    KEY idx_matches_usuario1 (usuario1_id, status),
    KEY idx_matches_usuario2 (usuario2_id, status),
    CONSTRAINT fk_match_u1
        FOREIGN KEY (usuario1_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_match_u2
        FOREIGN KEY (usuario2_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_match_bloqueado_por
        FOREIGN KEY (bloqueado_por) REFERENCES usuarios(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BLOQUEIOS
-- ============================================================
CREATE TABLE bloqueios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    bloqueado_id BIGINT UNSIGNED NOT NULL,
    motivo VARCHAR(255) NULL,
    data_bloqueio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bloqueio (usuario_id, bloqueado_id),
    KEY idx_bloqueio_usuario (usuario_id),
    KEY idx_bloqueio_bloqueado (bloqueado_id),
    CONSTRAINT fk_bloqueio_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bloqueio_bloqueado
        FOREIGN KEY (bloqueado_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PUBLICACOES / FEED
-- ============================================================
CREATE TABLE publicacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    conteudo TEXT NOT NULL,
    tema VARCHAR(80) NULL,
    imagem_url VARCHAR(500) NULL,
    data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pub_usuario (usuario_id, data_publicacao),
    KEY idx_pub_data (data_publicacao),
    KEY idx_pub_tema (tema),
    CONSTRAINT fk_pub_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CURTIDAS E COMENTARIOS DAS PUBLICACOES
-- A mesma tabela atende os dois tipos.
-- ============================================================
CREATE TABLE interacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    publicacao_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('curtida','comentario') NOT NULL,
    conteudo TEXT NULL,
    curtida_unica TINYINT NULL,
    data_interacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inter_pub (publicacao_id, tipo, data_interacao),
    KEY idx_inter_usuario (usuario_id, tipo),
    CONSTRAINT fk_inter_pub
        FOREIGN KEY (publicacao_id) REFERENCES publicacoes(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_inter_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garante uma curtida por usuario em cada publicacao.
CREATE UNIQUE INDEX uq_interacao_curtida
    ON interacoes (publicacao_id, usuario_id, tipo, curtida_unica);

-- ============================================================
-- COMUNIDADES E EVENTOS
-- A tabela grupos atende os dois tipos.
-- ============================================================
CREATE TABLE grupos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    criador_id BIGINT UNSIGNED NOT NULL,
    grupo_pai_id BIGINT UNSIGNED NULL,
    tipo ENUM('comunidade','evento') NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    tema VARCHAR(80) NULL,
    local_evento VARCHAR(255) NULL,
<<<<<<< HEAD
    cidade VARCHAR(100) NULL,
    estado CHAR(2) NULL,
=======
>>>>>>> 56925097664ae51b0b11dd31b29189b4488ae373
    data_evento DATETIME NULL,
    privada TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('ativo','cancelado') NOT NULL DEFAULT 'ativo',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_grupos_tipo (tipo, status, data_evento),
    KEY idx_grupos_criador (criador_id),
    CONSTRAINT fk_grupos_criador
        FOREIGN KEY (criador_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_grupos_pai
        FOREIGN KEY (grupo_pai_id) REFERENCES grupos(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grupo_membros (
    grupo_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    papel ENUM('membro','moderador','admin') NOT NULL DEFAULT 'membro',
    data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (grupo_id, usuario_id),
    KEY idx_gm_usuario (usuario_id),
    CONSTRAINT fk_gm_grupo
        FOREIGN KEY (grupo_id) REFERENCES grupos(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_gm_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MENSAGENS
-- Uma conversa é representada pelo match_id.
-- ============================================================
CREATE TABLE mensagens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    match_id BIGINT UNSIGNED NOT NULL,
    remetente_id BIGINT UNSIGNED NOT NULL,
    conteudo TEXT NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    data_envio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mensagens_match (match_id, data_envio),
    KEY idx_mensagens_remetente (remetente_id),
    CONSTRAINT fk_msg_match
        FOREIGN KEY (match_id) REFERENCES matches(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_msg_remetente
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DENUNCIAS
-- Pode denunciar usuario, mensagem e/ou publicacao.
-- ============================================================
CREATE TABLE denuncias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    denunciante_id BIGINT UNSIGNED NOT NULL,
    denunciado_id BIGINT UNSIGNED NOT NULL,
    mensagem_id BIGINT UNSIGNED NULL,
    publicacao_id BIGINT UNSIGNED NULL,
    motivo ENUM('assedio','discurso_odio','perfil_falso','spam','conteudo_improprio','outro') NOT NULL,
    descricao TEXT NULL,
    status ENUM('aberta','em_analise','resolvida','arquivada') NOT NULL DEFAULT 'aberta',
    data_denuncia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_denuncias_status (status),
    KEY idx_denuncias_denunciante (denunciante_id),
    KEY idx_denuncias_denunciado (denunciado_id),
    CONSTRAINT fk_den_denunciante
        FOREIGN KEY (denunciante_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_den_denunciado
        FOREIGN KEY (denunciado_id) REFERENCES usuarios(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_den_mensagem
        FOREIGN KEY (mensagem_id) REFERENCES mensagens(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_den_publicacao
        FOREIGN KEY (publicacao_id) REFERENCES publicacoes(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PROVAS DE DENUNCIA (recorte da conversa anexado por quem denuncia)
-- ============================================================
CREATE TABLE IF NOT EXISTS denuncia_evidencias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    denuncia_id BIGINT UNSIGNED NOT NULL,
    remetente_id BIGINT UNSIGNED NOT NULL,
    conteudo TEXT NOT NULL,
    data_envio DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_evid_denuncia (denuncia_id, data_envio),
    CONSTRAINT fk_evid_denuncia
        FOREIGN KEY (denuncia_id) REFERENCES denuncias(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_evid_remetente
        FOREIGN KEY (remetente_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INTERESSES INICIAIS
-- ============================================================
INSERT INTO interesses (nome, slug) VALUES
('Música', 'musica'),
('Filmes', 'filmes'),
('Séries', 'series'),
('Jogos', 'jogos'),
('RPG', 'rpg'),
('Anime', 'anime'),
('Livros', 'livros'),
('Tecnologia', 'tecnologia'),
('Programação', 'programacao'),
('Esportes', 'esportes'),
('Futebol', 'futebol'),
('Academia', 'academia'),
('Viagens', 'viagens'),
('Fotografia', 'fotografia'),
('Arte', 'arte'),
('Desenho', 'desenho'),
('Culinária', 'culinaria'),
('Memes', 'memes'),
('Cosplay', 'cosplay'),
('Natureza', 'natureza'),
('Animais', 'animais'),
('Política', 'politica'),
('Filosofia', 'filosofia'),
('História', 'historia');

-- ============================================================
-- FIM
-- ============================================================


-- ============================================================
-- AUDITORIA DO PAINEL ADMINISTRATIVO
-- ============================================================
CREATE TABLE auditoria_admin (
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

-- ============================================================
-- RECURSOS SOCIAIS (notificações, posts em comunidades, comentários
-- de eventos e redefinição de senha)
-- ============================================================
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

-- ============================================================
-- VERIFICAÇÃO EM DUAS ETAPAS: códigos de backup (só o hash é guardado)
-- ============================================================
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
