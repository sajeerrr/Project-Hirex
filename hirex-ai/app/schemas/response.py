# from pydantic import BaseModel

# class VerificationResponse(BaseModel):
#     status: str
#     verification_score: int
#     document: dict
#     face: dict
#     quality: dict

from pydantic import BaseModel
from typing import Optional


class FileData(BaseModel):
    government_id: str
    selfie: str
    certificate: Optional[str]


class VerificationData(BaseModel):
    score: int
    status: str
    ocr_status: str
    face_verified: bool
    face_match_score: float


class DocumentData(BaseModel):
    document_type: Optional[str]
    name: Optional[str]
    id_number: Optional[str]
    dob: Optional[str]
    gender: Optional[str]


class ResponseData(BaseModel):
    files: FileData
    verification: VerificationData
    document: DocumentData


class VerificationResponse(BaseModel):
    success: bool
    message: str
    data: ResponseData | None = None