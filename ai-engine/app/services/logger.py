import os
import json
from datetime import datetime

_DEBUG = os.environ.get("DEBUG", "").lower() in ("1", "true", "yes")


def enable():
    global _DEBUG
    _DEBUG = True


def disable():
    global _DEBUG
    _DEBUG = False


def log(service: str, msg: str, data=None):
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
