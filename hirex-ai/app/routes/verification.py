from fastapi import APIRouter
from fastapi import UploadFile, File
from typing import List
from app.services.ocr import extract_text
from app.services.image_quality import check_image_quality
from app.services.face_match import verify_face
from app.services.llm_parser import parse_document

from app.services.verification_engine import verify_worker
from app.schemas.response import VerificationResponse

from app.utils.file_handler import (
    save_file,
    ALLOWED_IMAGE_TYPES,
    ALLOWED_DOCUMENT_TYPES
)


router = APIRouter()


@router.post("/verify-worker", response_model=VerificationResponse)
async def verify_worker_sub(
    government_id: UploadFile = File(...),
    selfie: UploadFile = File(...),
    certificate: UploadFile | None = File(None)
):

    gov_path = save_file(government_id, "documents", ALLOWED_DOCUMENT_TYPES)

    selfie_path = save_file(selfie, "selfies", ALLOWED_IMAGE_TYPES)

    certificate_path = None

    if certificate:
        certificate_path = save_file(
            certificate,
            "certificates",
            ALLOWED_DOCUMENT_TYPES
        )    
    # portfolio_paths = []

    # for image in portfolio:
    #     path = save_file(
    #         image,
    #         "portfolio",
    #         ALLOWED_IMAGE_TYPES
    #      )
        
    #     portfolio_paths.append(path)

    report = verify_worker(
        gov_path,
        selfie_path,
        certificate_path
    )

    return report