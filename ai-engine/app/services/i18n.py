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
        return _translator("en").translate(text) or text
    except Exception as e:
        log("I18N", f"to_english failed: {str(e)[:60]}")
        return text


def from_english(text: str, target_lang: str) -> str:
    """Translate English text to the user's language. English is a passthrough."""
    if not text or not text.strip():
        return text
    if not target_lang or target_lang == "en":
        return text
    try:
        return _translator(target_lang).translate(text) or text
    except Exception as e:
        log("I18N", f"from_english failed: {str(e)[:60]}")
        return text


def translate_list(items: List[str], target_lang: str) -> List[str]:
    """Translate a list of English strings to the user's language (in order)."""
    if not target_lang or target_lang == "en":
        return items
    out = []
    for it in items:
        out.append(from_english(it, target_lang))
    return out


def translate_dict_values(payload: dict, target_lang: str, keys: List[str]) -> dict:
    """Translate selected string values of a dict to the user's language."""
    if not target_lang or target_lang == "en":
        return payload
    for k in keys:
        if isinstance(payload.get(k), str):
            payload[k] = from_english(payload[k], target_lang)
    return payload


def translate_batch(items: List[str], target_lang: str) -> List[str]:
    """Translate a list of English strings concurrently (one round-trip of latency).

    English target is a passthrough.
    """
    if not target_lang or target_lang == "en":
        return items
    if not items:
        return items
    try:
        # _translator() must be called INSIDE each thread so it gets the
        # thread-local translator instance (not the main thread's)
        futures = [_POOL.submit(lambda t=it: _translator(target_lang).translate(t)) for it in items]
        return [f.result() or it for f, it in zip(futures, items)]
    except Exception as e:
        log("I18N", f"translate_batch failed: {str(e)[:60]}")
        return items
