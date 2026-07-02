-- Run this entire script in Supabase SQL Editor
-- https://supabase.com/dashboard/project/mjgdenxecodkkjgoejww/sql/new

-- 1. Enable pgvector
create extension if not exists vector;

-- 2. Create the table
create table if not exists public.disease_embeddings (
  id text primary key,
  document text not null,
  embedding vector(384) not null,
  name_en text,
  name_ar text,
  severity text,
  severity_ar text,
  specialist text,
  specialist_ar text,
  symptoms_en text,
  symptoms_ar text
);

-- 3. HNSW index for cosine similarity
create index if not exists idx_disease_embeddings_cosine
  on public.disease_embeddings
  using hnsw (embedding vector_cosine_ops);

-- 4. RPC: ping
create or replace function ping_pgvector()
returns text
language sql
as $$
  select 'pong'::text;
$$;

-- 5. RPC: insert a disease embedding
create or replace function insert_disease_embedding(
  disease_id text,
  doc text,
  query_embedding vector(384),
  name_en text default null,
  name_ar text default null,
  severity text default null,
  severity_ar text default null,
  specialist text default null,
  specialist_ar text default null,
  symptoms_en text default null,
  symptoms_ar text default null
)
returns void
language sql
as $$
  insert into public.disease_embeddings
    (id, document, embedding, name_en, name_ar,
     severity, severity_ar, specialist, specialist_ar,
     symptoms_en, symptoms_ar)
  values
    (disease_id, doc, query_embedding, name_en, name_ar,
     severity, severity_ar, specialist, specialist_ar,
     symptoms_en, symptoms_ar)
  on conflict (id) do nothing;
$$;

-- 6. RPC: search diseases by cosine similarity
create or replace function search_diseases(
  query_embedding vector(384),
  match_count int default 5
)
returns table(
  id text,
  document text,
  name_en text,
  name_ar text,
  specialist_ar text,
  symptoms_ar text,
  similarity double precision
)
language sql
as $$
  select
    de.id,
    de.document,
    de.name_en,
    de.name_ar,
    de.specialist_ar,
    de.symptoms_ar,
    1 - (de.embedding <=> query_embedding) as similarity
  from public.disease_embeddings de
  order by de.embedding <=> query_embedding
  limit match_count;
$$;

-- 7. RPC: count all disease embeddings
create or replace function count_disease_embeddings()
returns bigint
language sql
as $$
  select count(*) from public.disease_embeddings;
$$;

-- 8. RPC: delete all disease embeddings
create or replace function delete_all_disease_embeddings()
returns void
language sql
as $$
  delete from public.disease_embeddings;
$$;
