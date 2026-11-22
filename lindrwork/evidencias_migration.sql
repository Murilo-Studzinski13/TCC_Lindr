-- Lindr: provas de denúncia (recorte da conversa anexado pela pessoa que denuncia).
-- Seguro para rodar mais de uma vez; não apaga nem altera dados existentes.
-- O administrador NÃO lê conversas: ele só vê as mensagens que quem denunciou anexou.
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
