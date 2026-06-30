from app.services.image_quality import check_image_quality
from app.services.ocr import extract_text
from app.services.llm_parser import parse_document
from app.services.face_match import verify_face
from app.services.scoring import calculate_score


def verify_worker(
    government_id: str,
    selfie: str,
    certificate: str | None
):
    
    quality = check_image_quality(government_id)
    if not quality["status"]:
        return {
            "success":False,
            "message":quality["reason"],
            "data": None
        }
    
    ocr_result = extract_text(government_id)

    document = parse_document(ocr_result)

    face = verify_face(government_id, selfie)

    score = calculate_score(
        quality,
        face,
        document,
        certificate is not None
    )

    # return {
    #     "status":"success",
    #     "verification_score": score,
    #     "document":document,
    #     "face":face,
    #     "quality":quality
    # }
    return {
        "success": True,
        "message": "Verification completed successfully.",
        "data": {
            "files": {
                "government_id": government_id,
                "selfie": selfie,
                "certificate": certificate
            },
            "verification": {
                "score": score,
                "status": "pending",
                "ocr_status": "success",
                "face_verified": face["verified"],
                "face_match_score": face["match_percentage"]
            },
            "document": document
        }
    }