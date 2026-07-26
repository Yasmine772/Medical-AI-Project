import sys; sys.path.insert(0,'.')
from app.services.pgvector_client import PgVectorClient
from app.services.embedding_service import EmbeddingService
store = PgVectorClient()
emb = EmbeddingService()
vec = emb.encode('Proteinuria')
results = store.search(vec, limit=5) or []
print('Results: %d' % len(results))
for i, r in enumerate(results):
    doc = (r.get('document') or '')[:200]
    ne = r.get('name_en','') or ''
    typ = r.get('type','') or ''
    print('\n[%d] type=%s name_en=[%s]' % (i, typ, ne))
    print('  doc: %s' % doc.replace('\n', ' ')[:150])
