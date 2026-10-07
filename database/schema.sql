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
