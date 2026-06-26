from fastapi import APIRouter
from fastapi import UploadFile, File
from typing import List
from app.utils.file_handler import (
    save_file,
    ALLOWED_IMAGE_TYPES,
    ALLOWED_DOCUMENT_TYPES
)


router = APIRouter()


@router.post("/verify-worker")
async def verify_worker(
    government_id: UploadFile = File(...),
    selfie: UploadFile = File(...),
    certificate: UploadFile | None = File(None),
    # portfolio: List[UploadFile] = File(...)
):

    gov_path = save_file(
        government_id,
        "documents",
        ALLOWED_DOCUMENT_TYPES
    )

    selfie_path = save_file(
        selfie,
        "selfies",
        ALLOWED_IMAGE_TYPES
    )

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

    return {
        "status": "success",
        "goverment_id": gov_path,
        "selfie": selfie_path,
        "certificate": certificate_path,
        # "portfolio": portfolio_paths
    }