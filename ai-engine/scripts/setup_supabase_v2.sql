-- ============================================================
-- v2 — Diagnosis sessions & history (interactive Q&A)
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

-- 2. Diagnosis history (completed diagnoses)
create table if not exists public.diagnosis_history (
  id text primary key,
  user_id text not null,
  session_id text references public.diagnosis_sessions(id),
  initial_symptoms text,
  disease_name text,
  disease_name_ar text,
  confidence text,
  specialist text,
  specialist_ar text,
  advice text,
  reasoning text,
  created_at timestamptz default now()
);

-- 3. RPC: create session
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

-- 4. RPC: get session
create or replace function get_diagnosis_session(p_id text)
returns jsonb
language sql
as $$
  select to_jsonb(s)
  from public.diagnosis_sessions s
  where s.id = p_id;
$$;

-- 5. RPC: update session conversation
create or replace function update_diagnosis_session(
  p_id text,
  p_conversation jsonb
)
returns void
language sql
as $$
  update public.diagnosis_sessions
  set conversation = p_conversation,
      updated_at = now()
  where id = p_id;
$$;

-- 6. RPC: complete session (save to history + mark done)
create or replace function complete_diagnosis_session(
  p_id text,
  p_conversation jsonb,
  p_disease_name text,
  p_disease_name_ar text,
  p_confidence text,
  p_specialist text,
  p_specialist_ar text,
  p_advice text,
  p_reasoning text
)
returns void
language plpgsql
as $$
begin
  update public.diagnosis_sessions
  set status = 'completed',
      conversation = p_conversation,
      updated_at = now()
  where id = p_id;

  insert into public.diagnosis_history
    (id, user_id, session_id, initial_symptoms,
     disease_name, disease_name_ar, confidence,
     specialist, specialist_ar, advice, reasoning)
  select
    gen_random_uuid()::text, s.user_id, s.id, s.initial_symptoms,
    p_disease_name, p_disease_name_ar, p_confidence,
    p_specialist, p_specialist_ar, p_advice, p_reasoning
  from public.diagnosis_sessions s
  where s.id = p_id;
end;
$$;

-- 7. RPC: get user history
create or replace function get_user_diagnosis_history(p_user_id text)
returns jsonb
language sql
as $$
  select coalesce(jsonb_agg(to_jsonb(h) order by h.created_at desc), '[]'::jsonb)
  from public.diagnosis_history h
  where h.user_id = p_user_id;
$$;
