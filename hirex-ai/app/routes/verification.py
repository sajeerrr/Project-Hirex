from fastapi import APIRouter
from fastapi import UploadFile, File
from typing import List
from app.services.ocr import extract_text
from app.services.image_quality import check_image_quality
from app.services.face_match import verify_face
from app.services.ocr_parser import parse_ocr_data
from app.services.llm_parser import parse_document
from app.services.verification_engine import verify_worker


from app.utils.file_handler import (
    save_file,
    ALLOWED_IMAGE_TYPES,
    ALLOWED_DOCUMENT_TYPES
)


router = APIRouter()


@router.post("/verify-worker")
async def verify_worker_sub(
    government_id: UploadFile = File(...),
    # selfie: UploadFile = File(...),
    # certificate: UploadFile | None = File(None),
    # portfolio: List[UploadFile] = File(...)
):

    gov_path = save_file(
        government_id,
        "documents",
        ALLOWED_DOCUMENT_TYPES
    )

    # quality = check_image_quality(gov_path)

    # if not quality["status"]:
    #     return {
    #         "status": "failed",
    #         "reason": quality["reason"]
    #     }

    # ocr_result = extract_text(gov_path)

    # print("\n".join(ocr_result))

    # parsed_data = parse_ocr_data(ocr_result)
    # parsed_data = parse_document(ocr_result)

    # selfie_path = save_file(
    #     selfie,
    #     "selfies",
    #     ALLOWED_IMAGE_TYPES
    # )

    # face_result = verify_face(gov_path, selfie_path)

    # certificate_path = None

    # if certificate:
    #     certificate_path = save_file(
    #         certificate,
    #         "certificates",
    #         ALLOWED_DOCUMENT_TYPES
    #     )
    
    # portfolio_paths = []

    # for image in portfolio:
    #     path = save_file(
    #         image,
    #         "portfolio",
    #         ALLOWED_IMAGE_TYPES
    #      )
        
    #     portfolio_paths.append(path)

    # return {
    #     "status": "success",
    #     "goverment_id": gov_path,
    #     "quality": quality,
    #     "ocr":parsed_data,
    #     # "face":face_result
    #     # "selfie": selfie_path,
    #     # "certificate": certificate_path,
    #     # "portfolio": portfolio_paths
    # }
    report = verify_worker(
        gov_path,
        # selfie_path,
        # certificate_path,
        # portfolio_paths
    )

    return report