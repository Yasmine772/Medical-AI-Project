"""LLM service: thin wrapper around Cloudflare Workers AI (OpenAI-compatible API).

The default model is Llama 3.1 8B for the diagnosis flow. The ``/symptoms``
endpoint overrides this with the smaller, faster Llama 3.2 3B model.
"""
import json
import os
from openai import OpenAI
from app.services.logger import log


class LLMService:
    """Client for Cloudflare Workers AI chat completions.

    Args:
        api_key (str, optional): Cloudflare API key. Defaults to the
            ``CLOUDFLARE_API_KEY`` environment variable.
        model (str): Default model ID used when a call does not override it.
    """

    def __init__(self, api_key: str = None, model: str = "@cf/meta/llama-3.1-8b-instruct-fp8"):
        key = api_key or os.environ.get("CLOUDFLARE_API_KEY")
        account_id = os.environ.get("CLOUDFLARE_ACCOUNT_ID")
        if not key or not account_id:
            raise ValueError("CLOUDFLARE_API_KEY and CLOUDFLARE_ACCOUNT_ID must be set")
        self._client = OpenAI(
            base_url=f"https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/v1/",
            api_key=key,
            timeout=120,
            max_retries=0,
        )
        self._model = model

    def ask(self, messages: list, temperature: float = 0.2, max_tokens: int = 4096, model: str = None) -> str:
        """Send a chat-completion request and return the assistant's text reply.

        Args:
            messages (list): OpenAI-style ``[{role, content}, ...]`` messages.
            temperature (float): Sampling temperature (0 = deterministic).
            max_tokens (int): Max completion tokens. Pass ``None`` to omit.
            model (str, optional): Model ID override; defaults to the instance model.

        Returns:
            str: The assistant's message content, as plain text.
        """
        actual = model or self._model
        kwargs = dict(model=actual, messages=messages, temperature=temperature)
        if max_tokens is not None:
            kwargs["max_tokens"] = max_tokens
        log("LLM", f"API call model={actual} temp={temperature} max={max_tokens} msgs={len(messages)}")
        resp = self._client.chat.completions.create(**kwargs)
        content = resp.choices[0].message.content or ""
        if not isinstance(content, str):
            content = json.dumps(content) if isinstance(content, (dict, list)) else str(content)
        log("LLM", f"API response type={type(content).__name__} tokens={resp.usage.total_tokens if resp.usage else '?'} len={len(content)}")
        return content
