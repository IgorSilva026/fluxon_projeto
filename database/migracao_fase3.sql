-- PostgreSQL / Supabase. Execute após database/schema.sql.
BEGIN;
CREATE TABLE IF NOT EXISTS public.finance_accounts (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    name varchar(80) NOT NULL CHECK (btrim(name) <> ''),
    connected boolean NOT NULL DEFAULT true,
    last_synced_at timestamptz,
    opening_cents bigint NOT NULL CHECK (opening_cents BETWEEN 0 AND 99999999999),
    UNIQUE (user_id, id)
);
ALTER TABLE public.finance_accounts ADD COLUMN IF NOT EXISTS connected boolean NOT NULL DEFAULT true;
ALTER TABLE public.finance_accounts ADD COLUMN IF NOT EXISTS last_synced_at timestamptz;
CREATE UNIQUE INDEX IF NOT EXISTS finance_account_names ON public.finance_accounts (user_id, lower(name));

CREATE TABLE IF NOT EXISTS public.finance_categories (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    name varchar(80) NOT NULL CHECK (btrim(name) <> ''),
    UNIQUE (user_id, id)
);
CREATE UNIQUE INDEX IF NOT EXISTS finance_category_names ON public.finance_categories (user_id, lower(name));

CREATE TABLE IF NOT EXISTS public.finance_rules (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    name varchar(80) NOT NULL CHECK (btrim(name) <> ''),
    rate_bp integer NOT NULL CHECK (rate_bp BETWEEN 1 AND 10000),
    due_date date,
    active boolean NOT NULL DEFAULT true,
    UNIQUE (user_id, id)
);
CREATE UNIQUE INDEX IF NOT EXISTS finance_rule_names ON public.finance_rules (user_id, lower(name));

CREATE TABLE IF NOT EXISTS public.finance_entries (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    account_id integer NOT NULL,
    category_id integer NOT NULL,
    description varchar(160) NOT NULL CHECK (btrim(description) <> ''),
    kind varchar(8) NOT NULL CHECK (kind IN ('entrada','saida')),
    amount_cents bigint NOT NULL CHECK (amount_cents BETWEEN 1 AND 99999999999),
    occurred_on date NOT NULL,
    external_ref varchar(60),
    reversed_at timestamptz,
    UNIQUE (user_id, id),
    UNIQUE (user_id, account_id, external_ref),
    FOREIGN KEY (user_id, account_id) REFERENCES public.finance_accounts(user_id,id) DEFERRABLE INITIALLY DEFERRED,
    FOREIGN KEY (user_id, category_id) REFERENCES public.finance_categories(user_id,id) DEFERRABLE INITIALLY DEFERRED
);
CREATE INDEX IF NOT EXISTS finance_entry_dates ON public.finance_entries (user_id, occurred_on DESC, id DESC);
CREATE INDEX IF NOT EXISTS finance_entry_account ON public.finance_entries (user_id, account_id, occurred_on DESC);
CREATE INDEX IF NOT EXISTS finance_entry_category ON public.finance_entries (user_id, category_id, occurred_on DESC);

CREATE TABLE IF NOT EXISTS public.finance_reserves (
    id integer GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id integer NOT NULL REFERENCES public.users(id) ON DELETE CASCADE,
    entry_id integer NOT NULL,
    rule_id integer NOT NULL,
    released_at timestamptz,
    purpose varchar(80) NOT NULL,
    due_date date,
    rate_bp integer NOT NULL CHECK (rate_bp BETWEEN 1 AND 10000),
    amount_cents bigint NOT NULL CHECK (amount_cents BETWEEN 0 AND 99999999999),
    UNIQUE (user_id, entry_id, rule_id),
    FOREIGN KEY (user_id, entry_id) REFERENCES public.finance_entries(user_id,id) ON DELETE CASCADE,
    FOREIGN KEY (user_id, rule_id) REFERENCES public.finance_rules(user_id,id) DEFERRABLE INITIALLY DEFERRED
);
ALTER TABLE public.finance_reserves ADD COLUMN IF NOT EXISTS released_at timestamptz;
CREATE INDEX IF NOT EXISTS finance_reserve_rule ON public.finance_reserves (user_id, rule_id);
ALTER TABLE public.finance_accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.finance_categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.finance_rules ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.finance_entries ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.finance_reserves ENABLE ROW LEVEL SECURITY;
DO $$
DECLARE role_name text;
BEGIN
    FOREACH role_name IN ARRAY ARRAY['anon','authenticated'] LOOP
        IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = role_name) THEN
            EXECUTE format('REVOKE ALL ON public.finance_accounts, public.finance_categories, public.finance_rules, public.finance_entries, public.finance_reserves FROM %I',role_name);
        END IF;
    END LOOP;
END $$;
COMMIT;
