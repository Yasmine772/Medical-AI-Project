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


class QuestionOption(BaseModel):
    id: str
    label: str


class SymptomQuestion(BaseModel):
    id: str
    text: str
    type: str
    options: List[QuestionOption]


class SymptomAnswers(BaseModel):
    session_id: str
    symptom_name: str
    answers: List[dict]
    symptoms_complete: bool = False


class FollowUpAnswer(BaseModel):
    session_id: str
    question_id: str
    answer: str


class DiagnosisRequest(BaseModel):
    gender: str = ""
    is_smoker: bool = False
    has_diabetes: bool = False
    has_hypertension: bool = False
    is_pregnant: Optional[bool] = None
    activity_level: str = "moderate"
    assessment_for: str = "myself"
