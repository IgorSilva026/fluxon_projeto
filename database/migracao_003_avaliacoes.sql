-- Incremento Avaliação. Reaplicável, sem apagar usuários ou avaliações.
USE fluxon;
CREATE TABLE IF NOT EXISTS user_reviews (
  user_id INT PRIMARY KEY,
  nota TINYINT UNSIGNED NOT NULL,
  comentario VARCHAR(1000) NOT NULL DEFAULT '',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_review_rating CHECK (nota BETWEEN 1 AND 5)
);
