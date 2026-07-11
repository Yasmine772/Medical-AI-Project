-- ============================================================
-- Fix search_embeddings RPC — drop broken overloads, 
-- recreate with proper filter logic and higher ef_search
-- Run this in Supabase SQL Editor
-- ============================================================

-- Drop ALL existing overloads
drop function if exists public.search_embeddings(vector(384), int);
drop function if exists public.search_embeddings(text, int, vector(384));
drop function if exists public.search_embeddings(vector(384), int, text);

-- Recreate with filter_type and higher ef_search
create or replace function search_embeddings(
  query_embedding vector(384),
  match_count int default 5,
  filter_type text default null
)
returns table(
  id text,
  document text,
  type text,
  name_en text,
  name_ar text,
  severity text,
  severity_ar text,
  specialist text,
  specialist_ar text,
  symptoms_en text,
  symptoms_ar text,
  source text,
  page int,
  chunk_index int,
  language text,
  similarity double precision
)
language plpgsql
as $$
begin
  perform set_config('hnsw.ef_search', '200', true);
  return query
    select
      e.id,
      e.document,
      e.type,
      e.name_en,
      e.name_ar,
      e.severity,
      e.severity_ar,
      e.specialist,
      e.specialist_ar,
      e.symptoms_en,
      e.symptoms_ar,
      e.source,
      e.page,
      e.chunk_index,
      e.language,
      1 - (e.embedding <=> query_embedding) as similarity
    from public.embeddings e
    where (filter_type is null or e.type = filter_type)
    order by e.embedding <=> query_embedding
    limit match_count;
end;
$$;

-- Also fix count_embeddings 
create or replace function count_embeddings(filter_type text default null)
returns bigint
language sql
as $$
  select count(*) from public.embeddings
  where (filter_type is null or type = filter_type);
$$;
