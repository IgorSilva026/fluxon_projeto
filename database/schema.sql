-- Fluxon Analytics — schema PostgreSQL para Supabase.
-- Cole no SQL Editor do Supabase (ou rode com psql) em um projeto novo.
-- O PHP conecta direto ao Postgres com o usuário "postgres" (que ignora RLS).
-- O RLS ligado no final bloqueia a API pública (anon key) de ler estas tabelas.

CREATE TABLE IF NOT EXISTS public.users (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE CHECK (email = lower(email)),
  senha_hash VARCHAR(255) NULL,            -- bcrypt (password_hash); NULL se só usa Google
  google_id VARCHAR(40) NULL,
  tentativas SMALLINT NOT NULL DEFAULT 0,  -- falhas de login seguidas
  bloqueado_ate TIMESTAMPTZ NULL,
  email_verificado_em TIMESTAMPTZ NULL,
  versao_sessao INTEGER NOT NULL DEFAULT 0,
  ultimo_reset TIMESTAMPTZ NULL,
  criado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.tokens (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  tipo VARCHAR(5) NOT NULL CHECK (tipo IN ('reset', '2fa')),
  hash CHAR(64) NOT NULL,                  -- SHA-256 do token/código (nunca em texto puro)
  expira TIMESTAMPTZ NOT NULL,
  tentativas SMALLINT NOT NULL DEFAULT 0,
  usado SMALLINT NOT NULL DEFAULT 0 CHECK (usado IN (0, 1))
);
CREATE INDEX IF NOT EXISTS tokens_user_tipo_idx ON public.tokens (user_id, tipo);
CREATE INDEX IF NOT EXISTS tokens_hash_idx ON public.tokens (hash);

CREATE TABLE IF NOT EXISTS public.user_profiles (
  user_id INTEGER PRIMARY KEY REFERENCES public.users(id) ON DELETE CASCADE,
  cnpj VARCHAR(18) NOT NULL DEFAULT '',
  tipo_empresa VARCHAR(16) NOT NULL DEFAULT 'autonomo',
  aliquota NUMERIC(5,2) NOT NULL DEFAULT 14.00,
  reserva_auto SMALLINT NOT NULL DEFAULT 1 CHECK (reserva_auto IN (0, 1)),
  alertas_vencimento SMALLINT NOT NULL DEFAULT 1 CHECK (alertas_vencimento IN (0, 1)),
  lembretes_cobranca SMALLINT NOT NULL DEFAULT 1 CHECK (lembretes_cobranca IN (0, 1)),
  telefone VARCHAR(20) NOT NULL DEFAULT '',
  cidade VARCHAR(100) NOT NULL DEFAULT '',
  ocupacao VARCHAR(100) NOT NULL DEFAULT '',
  notif_app SMALLINT NOT NULL DEFAULT 1 CHECK (notif_app IN (0, 1)),
  notif_email SMALLINT NOT NULL DEFAULT 0 CHECK (notif_email IN (0, 1)),
  frequencia VARCHAR(12) NOT NULL DEFAULT 'evento',
  horario TIME NULL,
  foto BYTEA NULL,
  foto_mime VARCHAR(30) NULL,
  atualizado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.user_reviews (
  user_id INTEGER PRIMARY KEY REFERENCES public.users(id) ON DELETE CASCADE,
  nota SMALLINT NOT NULL CHECK (nota BETWEEN 1 AND 5),
  comentario VARCHAR(1000) NOT NULL DEFAULT '',
  criado_em TIMESTAMPTZ NOT NULL DEFAULT now(),
  atualizado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.account_deletion_tokens (
  user_id INTEGER PRIMARY KEY REFERENCES public.users(id) ON DELETE CASCADE,
  codigo_hash VARCHAR(255) NOT NULL,
  solicitado_em TIMESTAMPTZ NOT NULL,
  expira_em TIMESTAMPTZ NOT NULL,
  tentativas SMALLINT NOT NULL DEFAULT 0,
  usado SMALLINT NOT NULL DEFAULT 0 CHECK (usado IN (0, 1))
);

-- Substitui o "ON UPDATE CURRENT_TIMESTAMP" do MySQL.
CREATE OR REPLACE FUNCTION public.set_atualizado_em() RETURNS trigger
LANGUAGE plpgsql SET search_path = '' AS $$
BEGIN
  NEW.atualizado_em := now();
  RETURN NEW;
END $$;

DROP TRIGGER IF EXISTS user_profiles_atualizado ON public.user_profiles;
CREATE TRIGGER user_profiles_atualizado BEFORE UPDATE ON public.user_profiles
  FOR EACH ROW EXECUTE FUNCTION public.set_atualizado_em();

DROP TRIGGER IF EXISTS user_reviews_atualizado ON public.user_reviews;
CREATE TRIGGER user_reviews_atualizado BEFORE UPDATE ON public.user_reviews
  FOR EACH ROW EXECUTE FUNCTION public.set_atualizado_em();

-- Segurança: o Supabase expõe o schema public via API REST. Sem RLS (e sem
-- policies), as chaves anon/authenticated não leem nem gravam nada aqui.
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_reviews ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.account_deletion_tokens ENABLE ROW LEVEL SECURITY;

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
    REVOKE ALL ON public.users, public.tokens, public.user_profiles,
      public.user_reviews, public.account_deletion_tokens FROM anon, authenticated;
    REVOKE EXECUTE ON FUNCTION public.set_atualizado_em() FROM anon, authenticated, PUBLIC;
  END IF;
END $$;


-- Rode UMA vez no SQL Editor do Supabase se você já tinha rodado o schema.sql antigo.
-- Pode repetir sem erro.

-- Lançamentos financeiros (entradas e saídas) de cada usuário.
CREATE TABLE IF NOT EXISTS public.lancamentos (
  id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
  descricao VARCHAR(120) NOT NULL,
  tipo VARCHAR(7) NOT NULL CHECK (tipo IN ('entrada', 'saida')),
  categoria VARCHAR(10) NOT NULL DEFAULT 'geral' CHECK (categoria IN ('geral', 'imposto')),
  valor NUMERIC(12,2) NOT NULL CHECK (valor > 0),
  data DATE NOT NULL,                      -- data de vencimento / recebimento
  concluido SMALLINT NOT NULL DEFAULT 0 CHECK (concluido IN (0, 1)),  -- 1 = recebido/pago
  criado_em TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS lancamentos_user_data_idx ON public.lancamentos (user_id, data);
ALTER TABLE public.lancamentos ENABLE ROW LEVEL SECURITY;
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
    REVOKE ALL ON public.lancamentos FROM anon, authenticated;
  END IF;
END $$;