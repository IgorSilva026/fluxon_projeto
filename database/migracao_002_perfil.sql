-- Fase 2, incremento Minha Conta. Banco já atualizado pela migracao_001.
-- Pode ser reaplicado: não apaga nem recria os usuários existentes.
USE fluxon;
CREATE TABLE IF NOT EXISTS user_profiles (
  user_id INT PRIMARY KEY,
  telefone VARCHAR(20) NOT NULL DEFAULT '',
  cidade VARCHAR(100) NOT NULL DEFAULT '',
  ocupacao VARCHAR(100) NOT NULL DEFAULT '',
  notif_app TINYINT NOT NULL DEFAULT 1,
  notif_email TINYINT NOT NULL DEFAULT 0,
  frequencia VARCHAR(12) NOT NULL DEFAULT 'evento',
  horario TIME NULL,
  foto MEDIUMBLOB NULL,
  foto_mime VARCHAR(30) NULL,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
