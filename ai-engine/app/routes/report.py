from fastapi import APIRouter, HTTPException
from fastapi.responses import FileResponse, Response
from pydantic import BaseModel
from app.services.report_service import (
    generate_pdf,
    save_pdf,
    get_pdf_path,
    generate_report_html,
)

router = APIRouter()


class GenerateReportResponse(BaseModel):
    session_id: str
    pdf_path: str
    message: str = "Report generated successfully."


@router.post("/generate-report/{session_id}")
async def generate_report(session_id: str):
    try:
        pdf_bytes = generate_pdf(session_id)
        pdf_path = save_pdf(session_id, pdf_bytes)
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


@router.get("/reports/{session_id}/download")
async def download_report(session_id: str):
    pdf_path = get_pdf_path(session_id)
    if not pdf_path:
        try:
            pdf_bytes = generate_pdf(session_id)
            pdf_path = save_pdf(session_id, pdf_bytes)
        except ValueError as e:
            raise HTTPException(status_code=404, detail=str(e))
        except Exception as e:
            raise HTTPException(status_code=500, detail=f"Report generation failed: {str(e)}")

    return FileResponse(
        pdf_path,
        media_type="application/pdf",
        filename=f"diagnostic_report_{session_id[:8]}.pdf",
        headers={
            "Content-Disposition": f"attachment; filename=\"diagnostic_report_{session_id[:8]}.pdf\""
        },
    )


@router.get("/reports/{session_id}/preview")
async def preview_report(session_id: str):
    try:
        html = generate_report_html(session_id)
        return Response(content=html, media_type="text/html; charset=utf-8")
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
