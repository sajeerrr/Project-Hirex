from fastapi import APIRouter
from fastapi import UploadFile, File
from typing import List

router = APIRouter()


@router.post("/verify-worker")
async def verify_worker(
    government_id: UploadFile = File(...),
    selfie: UploadFile = File(...),
    certificate: UploadFile | None = File(None),
    portfolio: List[UploadFile] = File(...)
):

    return {
        "message": "Files received successfully",
        "government_id": government_id.filename,
        "selfie": selfie.filename,
        "certificate": (
            certificate.filename
            if certificate else None
        ),
        "portfolio_images": len(portfolio)
    }