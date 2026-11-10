CREATE DATABASE IF NOT EXISTS fluxon CHARACTER SET utf8mb4;
USE fluxon;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NULL,          -- bcrypt (password_hash); NULL se só usa Google
  google_id VARCHAR(40) NULL,
  tentativas TINYINT NOT NULL DEFAULT 0, -- falhas de login seguidas
  bloqueado_ate DATETIME NULL,
  email_verificado_em DATETIME NULL,
  versao_sessao INT NOT NULL DEFAULT 0,
  ultimo_reset DATETIME NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tipo ENUM('reset','2fa') NOT NULL,
  hash CHAR(64) NOT NULL,                -- SHA-256 do token/código (nunca em texto puro)
  expira DATETIME NOT NULL,
  tentativas TINYINT NOT NULL DEFAULT 0,
  usado TINYINT NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_profiles (
  user_id INT PRIMARY KEY,
  cnpj VARCHAR(18) NOT NULL DEFAULT '',
  tipo_empresa VARCHAR(16) NOT NULL DEFAULT 'autonomo',
  aliquota DECIMAL(5,2) NOT NULL DEFAULT 14.00,
  reserva_auto TINYINT NOT NULL DEFAULT 1,
  alertas_vencimento TINYINT NOT NULL DEFAULT 1,
  lembretes_cobranca TINYINT NOT NULL DEFAULT 1,
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

CREATE TABLE IF NOT EXISTS user_reviews (
  user_id INT PRIMARY KEY,
  nota TINYINT UNSIGNED NOT NULL,
  comentario VARCHAR(1000) NOT NULL DEFAULT '',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (nota BETWEEN 1 AND 5)
);

CREATE TABLE IF NOT EXISTS account_deletion_tokens (
  user_id INT PRIMARY KEY,
  codigo_hash VARCHAR(255) NOT NULL,
  solicitado_em DATETIME NOT NULL,
  expira_em DATETIME NOT NULL,
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  usado TINYINT NOT NULL DEFAULT 0,
  CONSTRAINT fk_deletion_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
