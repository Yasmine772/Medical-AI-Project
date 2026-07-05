-- ============================================================
-- v2 — Diagnosis sessions (interactive Q&A)
-- Run this AFTER setup_supabase.sql in the Supabase SQL Editor
-- ============================================================

-- 1. Diagnosis sessions (ongoing interactive conversations)
create table if not exists public.diagnosis_sessions (
  id text primary key,
  user_id text not null,
  status text not null default 'in_progress',
  initial_symptoms text not null,
  conversation jsonb not null default '[]'::jsonb,
  candidates jsonb,
  created_at timestamptz default now(),
  updated_at timestamptz default now()
);

-- 2. RPC: create session
create or replace function create_diagnosis_session(
  p_id text,
  p_user_id text,
  p_initial_symptoms text,
  p_candidates jsonb default null
)
returns void
language sql
as $$
  insert into public.diagnosis_sessions
    (id, user_id, initial_symptoms, candidates)
  values
    (p_id, p_user_id, p_initial_symptoms, p_candidates);
$$;

-- 3. RPC: get session
create or replace function get_diagnosis_session(p_id text)
returns jsonb
language sql
as $$
  select to_jsonb(s)
  from public.diagnosis_sessions s
  where s.id = p_id;
$$;

-- 4. RPC: update session (conversation + status)
create or replace function update_diagnosis_session(
  p_id text,
  p_conversation jsonb,
  p_status text default null
)
returns void
language sql
as $$
  update public.diagnosis_sessions
  set conversation = p_conversation,
      status = coalesce(p_status, status),
      updated_at = now()
  where id = p_id;
$$;
