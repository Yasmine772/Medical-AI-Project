import os
import httpx
from fastapi import APIRouter, HTTPException

router = APIRouter()


@router.get("/models")
async def list_models():
    api_key = os.environ.get("CLOUDFLARE_API_KEY")
    account_id = os.environ.get("CLOUDFLARE_ACCOUNT_ID")
    if not api_key or not account_id:
        raise HTTPException(status_code=500, detail="CLOUDFLARE_API_KEY and CLOUDFLARE_ACCOUNT_ID not set")

    try:
        async with httpx.AsyncClient() as client:
            resp = await client.get(
                f"https://api.cloudflare.com/client/v4/accounts/{account_id}/ai/models/search?per_page=50",
                headers={"Authorization": f"Bearer {api_key}"},
            )
            data = resp.json()
            if not data.get("success"):
                raise HTTPException(status_code=502, detail=data.get("errors", "Cloudflare API error"))

            models = [
                m["name"] for m in data.get("result", [])
                if "instruct" in m.get("name", "") and "vision" not in m.get("name", "")
            ]
            return {"status": "success", "data": models}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=502, detail=str(e))