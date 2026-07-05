from pydantic import BaseModel
from typing import List, Optional


class DiseaseItem(BaseModel):
    id: Optional[str] = None
    name: str
    name_ar: str
    symptoms: List[str]
    symptoms_ar: List[str]
    severity: str = ""
    severity_ar: str = ""
    specialist: str = ""
    specialist_ar: str = ""
    likelihoods: str = ""
    description: str = ""
    advice: str = ""


class DiagnoseRequest(BaseModel):
    symptoms: str
    language: str = "auto"


class DiagnoseResponse(BaseModel):
    disease_name: str
    disease_name_ar: str
    confidence: str
    specialist: str
    specialist_ar: str
    advice: str
    reasoning: str
