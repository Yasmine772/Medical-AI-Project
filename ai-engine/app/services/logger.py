"""Minimal debug logger.

Respects the ``DEBUG`` environment variable; when off, ``log()`` is a no-op.
Used across the app to trace vector search, LLM calls, Bayesian updates, etc.
"""
import os
import json
from datetime import datetime

_DEBUG = os.environ.get("DEBUG", "").lower() in ("1", "true", "yes")


def enable():
    """Force debug logging on at runtime."""
    global _DEBUG
    _DEBUG = True


def disable():
    """Force debug logging off at runtime."""
    global _DEBUG
    _DEBUG = False


def log(service: str, msg: str, data=None):
    """Print a timestamped, service-tagged debug line.

    Args:
        service (str): Short tag grouping the message (e.g. ``SYMPTOMS``).
        msg (str): Message text.
        data: Optional value; dicts/lists are pretty-printed (truncated).
    """
    if not _DEBUG:
        return
    ts = datetime.now().strftime("%H:%M:%S.%f")[:-3]
    line = f"[{ts}] [{service}] {msg}"
    if data is not None:
        if isinstance(data, (dict, list)):
            line += f"\n  {json.dumps(data, ensure_ascii=True, indent=2)[:2000]}"
        else:
            line += f" {data}"
    try:
        print(line)
    except UnicodeEncodeError:
        print(line.encode("ascii", errors="replace").decode("ascii"))
