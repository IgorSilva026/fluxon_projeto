-- Fase 2: confirmação de exclusão. Reaplicável, sem apagar dados existentes.
USE fluxon;
CREATE TABLE IF NOT EXISTS account_deletion_tokens (
  user_id INT PRIMARY KEY,
  codigo_hash VARCHAR(255) NOT NULL,
  solicitado_em DATETIME NOT NULL,
  expira_em DATETIME NOT NULL,
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  usado TINYINT NOT NULL DEFAULT 0,
  CONSTRAINT fk_deletion_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
