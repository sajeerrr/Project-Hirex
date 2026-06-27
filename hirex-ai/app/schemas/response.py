from pydantic import BaseModel

class VerificationResponse(BaseModel):
    status: str
    verification_score: int
    document: dict
    face: dict
    quality: dict