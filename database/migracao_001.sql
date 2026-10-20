-- Banco existente: execute UMA VEZ, após backup. Não reimporte schema.sql.
USE fluxon;
ALTER TABLE users
 ADD COLUMN email_verificado_em DATETIME NULL,
 ADD COLUMN versao_sessao INT NOT NULL DEFAULT 0,
 ADD COLUMN ultimo_reset DATETIME NULL;
UPDATE tokens SET usado=1;
