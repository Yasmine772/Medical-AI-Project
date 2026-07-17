import os
from openai import OpenAI
from app.services.logger import log


class LLMService:

    def __init__(self, api_key: str = None, model: str = "llama-3.1-8b-instant"):
        key = api_key or os.environ.get("GROQ_KEY")
        if not key:
            raise ValueError("GROQ_KEY not set")
        self._client = OpenAI(
            base_url="https://api.groq.com/openai/v1",
            api_key=key,
        )
        self._model = model

    def ask(self, messages: list, temperature: float = 0.2, max_tokens: int = 1024, model: str = None) -> str:
        actual = model or self._model
        log("LLM", f"API call model={actual} temp={temperature} max={max_tokens} msgs={len(messages)}")
        resp = self._client.chat.completions.create(
            model=actual,
            messages=messages,
            temperature=temperature,
            max_tokens=max_tokens,
            response_format={"type": "json_object"},
        )
        content = resp.choices[0].message.content or ""
        log("LLM", f"API response tokens={resp.usage.total_tokens if resp.usage else '?'} len={len(content)}")
        return content

    def ask_raw(self, messages: list, temperature: float = 0.2, max_tokens: int = 1024) -> str:
        log("LLM", f"API raw call model={self._model} temp={temperature} max={max_tokens} msgs={len(messages)}")
        resp = self._client.chat.completions.create(
            model=self._model,
            messages=messages,
            temperature=temperature,
            max_tokens=max_tokens,
        )
        content = resp.choices[0].message.content or ""
        log("LLM", f"API raw response tokens={resp.usage.total_tokens if resp.usage else '?'} len={len(content)}")
        return content
