import time
from bs4 import BeautifulSoup
from ddgs import DDGS
from primp import Client as PrimpClient
from deep_translator import GoogleTranslator
from app.services.logger import log

_LAST_DDG = 0.0
_TRANSLATOR = None


def _get_translator():
    global _TRANSLATOR
    if _TRANSLATOR is None:
        _TRANSLATOR = GoogleTranslator(source="auto", target="en")
    return _TRANSLATOR


def translate_to_en(text: str) -> str:
    try:
        return _get_translator().translate(text) or text
    except Exception as e:
        log("TRANS", f"Translation failed", str(e)[:80])
        return text


_translator_ar = None


def _get_translator_ar():
    global _translator_ar
    if _translator_ar is None:
        _translator_ar = GoogleTranslator(source="en", target="ar")
    return _translator_ar


def translate_to_ar(text: str) -> str:
    if not text:
        return ""
    try:
        return _get_translator_ar().translate(text) or text
    except Exception as e:
        log("TRANS", f"AR translation failed", str(e)[:80])
        return text


def _rate_limited_ddg(keywords: str, max_results: int = 5) -> list:
    global _LAST_DDG
    elapsed = time.time() - _LAST_DDG
    if elapsed < 0.8:
        time.sleep(0.8 - elapsed)
    _LAST_DDG = time.time()
    return list(DDGS().text(keywords, max_results=max_results))


def _scrape_url(url: str, max_chars: int = 3000) -> str | None:
    try:
        client = PrimpClient(verify=True, impersonate="chrome_125")
        resp = client.get(url, timeout=10)
        if resp.status_code != 200:
            return None

        soup = BeautifulSoup(resp.text, "html.parser")
        for tag in soup(["script", "style", "nav", "footer", "header", "aside"]):
            tag.decompose()

        article = soup.find("article") or soup.find("main")
        text = article.get_text(separator="\n", strip=True) if article else ""
        if not text:
            text = soup.body.get_text(separator="\n", strip=True) if soup.body else ""

        lines = [l.strip() for l in text.split("\n") if l.strip()]
        return "\n".join(lines)[:max_chars]
    except Exception as e:
        log("SCRAPE", f"Failed {url[:70]}", str(e)[:80])
        return None


def search_web(query: str, limit: int = 5) -> list:
    translated = translate_to_en(query)
    if translated != query:
        log("TRANS", f"Translated: '{query[:50]}' -> '{translated[:50]}'")
        query = translated
    log("WEB", f"Searching DDG: '{query[:80]}' limit={limit}")
    try:
        results = _rate_limited_ddg(query, max_results=limit * 3)
    except Exception as e:
        log("WEB", f"DDG search failed", str(e)[:100])
        return []

    enriched = []
    for r in results:
        if len(enriched) >= limit:
            break
        url = r.get("href", "")
        title = r.get("title", "")
        if not url or not title:
            continue
        content = _scrape_url(url)
        if content is None:
            continue
        enriched.append({"title": title, "url": url, "content": content})

    log("WEB", f"Returning {len(enriched)}/{limit} scraped results")
    return enriched
