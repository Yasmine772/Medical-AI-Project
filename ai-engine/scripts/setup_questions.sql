-- ============================================================
-- Questions table — one row per generated question, queryable by id
-- Run in Supabase SQL Editor (after setup_supabase_v2.sql)
-- ============================================================

create table if not exists public.diagnosis_questions (
  id uuid primary key default gen_random_uuid(),
  session_id text not null references public.diagnosis_sessions(id) on delete cascade,
  question_index int not null,
  question_jsonb jsonb not null,
  created_at timestamptz default now()
);

create index if not exists idx_diagnosis_questions_session
  on public.diagnosis_questions(session_id);

-- RPC to insert a question and return its id
create or replace function insert_diagnosis_question(
  p_session_id text,
  p_question_index int,
  p_question_jsonb jsonb
)
returns uuid
language sql
as $$
  insert into public.diagnosis_questions
    (session_id, question_index, question_jsonb)
  values
    (p_session_id, p_question_index, p_question_jsonb)
  returning id;
$$;

-- RPC to fetch a single question by id
create or replace function get_diagnosis_question(p_id uuid)
returns jsonb
language sql
as $$
  select to_jsonb(q)
  from public.diagnosis_questions q
  where q.id = p_id;
$$;

-- RPC to list all questions for a session (ordered)
create or replace function list_diagnosis_questions(p_session_id text)
returns jsonb
language sql
as $$
  select coalesce(jsonb_agg(to_jsonb(q) order by q.question_index), '[]'::jsonb)
  from public.diagnosis_questions q
  where q.session_id = p_session_id;
$$;
