"""Single source of truth for all language handling.

Everything the user sends is normalized to English for LLM processing,
and everything returned to the user is translated back to their language.
English is a passthrough (no calls, no cost, no latency).
"""
import threading
from concurrent.futures import ThreadPoolExecutor
from typing import List, Optional

from deep_translator import GoogleTranslator

from app.services.logger import log

# Thread-local storage so each thread gets its own GoogleTranslator instance
# (GoogleTranslator has mutable internal state — not safe to share across threads)
_TRANSLATOR_LOCAL = threading.local()

# Reusable thread pool for parallel translations
_POOL = ThreadPoolExecutor(max_workers=8)


# Curated medical glossary: overrides Google Translate for clinical descriptor
# terms it routinely mistranslates. Example: English "dull" -> Arabic "ممل"
# (boring) when the correct clinical term is "باهت". Keyed by target language;
# matched on the lowercased full string (covers short option labels like
# "dull", "sharp", "burning").
MEDICAL_GLOSSARY = {
    "ar": {
        "dull": "باهت",
        "sharp": "حاد",
        "burning": "حارق",
        "throbbing": "نابض",
        "stabbing": "طاعن",
        "aching": "موجِع",
        "cramping": "تشنّجي",
        "tingling": "تنميل",
        "numb": "خدر",
        "tickly": "دغدغة",
        "dry": "جاف",
        "wet": "رطب",
        "productive": "مصحوب ببلغم",
        "barking": "نباحي",
        "wheezy": "صفيري",
        "gradual": "تدريجي",
        "sudden": "مفاجئ",
        "constant": "مستمر",
        "intermittent": "متقطع",
        "mild": "خفيف",
        "moderate": "متوسط",
        "severe": "شديد",
        "better": "أفضل",
        "worse": "أسوأ",
    },
}

# Post-translation fixes: known bad Arabic tokens Google emits inside longer
# sentences, replaced with the correct clinical term.
ARABIC_TOKEN_FIXES = {
    "ممل": "باهت",
    "صعلك": "سعالك",
    "الصعل": "السعال",
}



def _translator(target: str) -> GoogleTranslator:
    key = target.lower()
    if not hasattr(_TRANSLATOR_LOCAL, "_cache"):
        _TRANSLATOR_LOCAL._cache = {}
    cached = _TRANSLATOR_LOCAL._cache.get(key)
    if cached is None:
        cached = GoogleTranslator(source="auto", target=target)
        _TRANSLATOR_LOCAL._cache[key] = cached
    return cached


def detect_lang(text: str) -> str:
    """Return an ISO language code for the given text.

    Falls back to script-range heuristics (Arabic, etc.) and then to 'en'.
    """
    if not text or not text.strip():
        return "en"
    # Fast script-range check for common non-Latin scripts
    for ch in text:
        if "\u0600" <= ch <= "\u06ff" or "\u0750" <= ch <= "\u077f":
            return "ar"
        if "\u0400" <= ch <= "\u04ff":
            return "ru"
        if "\u4e00" <= ch <= "\u9fff":
            return "zh-CN"
        if "\uac00" <= ch <= "\ud7a3":
            return "ko"
        if "\u3040" <= ch <= "\u30ff":
            return "ja"
    return "en"


def to_english(text: str) -> str:
    """Translate arbitrary user text to English. English is a passthrough."""
    if not text or not text.strip():
        return text
    lang = detect_lang(text)
    if lang == "en":
        return text
    try:
        future = _POOL.submit(lambda: _translator("en").translate(text))
        return future.result(timeout=8) or text
    except Exception as e:
        log("I18N", f"to_english failed: {str(e)[:60]}")
        return text


def from_english(text: str, target_lang: str) -> str:
    """Translate English text to the user's language. English is a passthrough."""
    if not text or not text.strip():
        return text
    if not target_lang or target_lang == "en":
        return text
    # Exact-match glossary override for short clinical descriptor labels.
    gloss = MEDICAL_GLOSSARY.get(target_lang)
    if gloss:
        mapped = gloss.get(text.strip().lower())
        if mapped:
            return mapped
    try:
        future = _POOL.submit(lambda: _translator(target_lang).translate(text))
        result = future.result(timeout=8)
        if result and len(result) < len(text) * 10 and "Error" not in result and "500" not in result:
            out = result
        else:
            out = text
    except Exception as e:
        log("I18N", f"from_english failed: {str(e)[:60]}")
        out = text
    # Post-translation token fixes (Arabic only).
    if target_lang.startswith("ar"):
        for bad, good in ARABIC_TOKEN_FIXES.items():
            out = out.replace(bad, good)
    return out


def translate_list(items: List[str], target_lang: str) -> List[str]:
    """Translate a list of English strings to the user's language (in order)."""
    if not target_lang or target_lang == "en":
        return items
    out = []
    for it in items:
        out.append(from_english(it, target_lang))
    return out


def translate_batch(items: List[str], target_lang: str) -> List[str]:
    """Translate a list of English strings concurrently (one round-trip of latency).

    English target is a passthrough. Each item is translated independently, so a
    single failing translation (GoogleTranslator quota/lang-detect/empty input)
    falls back to the original string instead of raising a 500 on /symptoms.
    """
    if not target_lang or target_lang == "en":
        return items
    if not items:
        return items
    # _translator() must be called INSIDE each thread so it gets the
    # thread-local translator instance (not the main thread's).
    futures = {
        _POOL.submit(lambda t=it: _translator(target_lang).translate(t)): it
        for it in items
    }
    out = []
    for f, it in futures.items():
        try:
            res = f.result(timeout=10)
            # Google Translate rate-limits with an HTTP 500 whose body is an error
            # page ("Error 500 (Server Error)!!..."). Never surface that to the
            # user — fall back to the original English string instead.
            if (
                isinstance(res, str)
                and res.strip()
                and "Error" not in res
                and "500" not in res
                and len(res) < len(it) * 15 + 50
            ):
                out.append(res)
            else:
                out.append(it)
        except Exception as e:
            log("I18N", f"translate_batch item failed: {str(e)[:60]}")
            out.append(it)
    return out
