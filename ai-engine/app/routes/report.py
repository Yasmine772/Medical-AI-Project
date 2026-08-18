"""PDF report generation, download, and JSON preview endpoints."""
from fastapi import APIRouter, HTTPException, Query, Request
from fastapi.responses import FileResponse
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel
from app.services.report_service import (
    generate_pdf,
    save_pdf,
    get_pdf_path,
    generate_report_html,
    build_report_json,
)

router = APIRouter()


class GenerateReportResponse(BaseModel):
    session_id: str
    pdf_path: str
    message: str = "Report generated successfully."


@router.post("/generate-report/{session_id}")
async def generate_report(
    session_id: str,
    request: Request,
    language_code: str = Query(default="en", description="Language code for the report content"),
):
    """Generate and store a PDF report for a session in the requested language."""
    overrides = None
    content_type = request.headers.get("content-type", "")
    if content_type.startswith("application/json"):
        try:
            body = await request.json()
        except Exception:
            body = {}
        if body.get("language_code"):
            language_code = body["language_code"]
        overrides = {
            "diagnoses": body.get("diagnoses"),
            "patient_info": body.get("patient_info"),
            "initial_symptoms": body.get("initial_symptoms"),
            "doctor_review": body.get("doctor_review"),
            "advice": body.get("advice"),
        }
        overrides = {k: v for k, v in overrides.items() if v is not None}

    reviewed = bool(overrides and overrides.get("doctor_review"))

    try:
        pdf_bytes = await run_in_threadpool(generate_pdf, session_id, language_code, overrides)
        pdf_path = save_pdf(session_id, pdf_bytes, language_code, reviewed=reviewed)
        return GenerateReportResponse(
            session_id=session_id,
            pdf_path=pdf_path,
        )
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except RuntimeError as e:
        raise HTTPException(status_code=500, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Report generation failed: {str(e)}")


@router.post("/reports/{session_id}/download")
async def download_report(
    session_id: str,
    request: Request,
    language_code: str = Query(default="en", description="Language code for the report content"),
    reviewed: bool = Query(default=False, description="Serve the doctor-reviewed report file"),
):
    """Download the PDF report.

    Four-condition cache/generation logic:
      1. Not reviewed + cached  → return the cached file.
      2. Not reviewed + missing  → generate (no overrides) and return.
      3. Reviewed     + cached  → return the cached reviewed file.
      4. Reviewed     + missing → generate from override data (reviewed) and return.
    """
    # Accept the same optional JSON body as /generate-report (kept optional so
    # callers that don't send a body still hit the cache-first path).
    overrides = None
    content_type = request.headers.get("content-type", "")
    if content_type.startswith("application/json"):
        try:
            body = await request.json()
        except Exception:
            body = {}
        if body.get("language_code"):
            language_code = body["language_code"]
        overrides = {
            "diagnoses": body.get("diagnoses"),
            "patient_info": body.get("patient_info"),
            "initial_symptoms": body.get("initial_symptoms"),
            "doctor_review": body.get("doctor_review"),
            "advice": body.get("advice"),
        }
        overrides = {k: v for k, v in overrides.items() if v is not None}

    pdf_path = get_pdf_path(session_id, language_code, reviewed=reviewed)

    # Cache miss → generate.
    if not pdf_path:
        try:
            pdf_bytes = await run_in_threadpool(
                generate_pdf, session_id, language_code, overrides if reviewed else None
            )
            pdf_path = save_pdf(session_id, pdf_bytes, language_code, reviewed=reviewed)
        except ValueError as e:
            raise HTTPException(status_code=404, detail=str(e))
        except Exception as e:
            raise HTTPException(status_code=500, detail=f"Report generation failed: {str(e)}")

    if not pdf_path:
        raise HTTPException(status_code=404, detail="Report not found")

    return FileResponse(
        pdf_path,
        media_type="application/pdf",
        filename=f"{'final_' if reviewed else 'diagnostic_'}report_{session_id[:8]}_{language_code}.pdf",
        headers={
            "Content-Disposition": f"attachment; filename=\"{'final_' if reviewed else 'diagnostic_'}report_{session_id[:8]}_{language_code}.pdf\""
        },
    )


@router.get("/reports/{session_id}/preview")
async def preview_report(session_id: str, language_code: str = Query(default="en", description="Language code for localized content")):
    """Return the report data as JSON (for in-app preview)."""
    try:
        return await run_in_threadpool(build_report_json, session_id, language_code)
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
