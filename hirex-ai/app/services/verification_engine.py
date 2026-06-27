from app.services.image_quality import check_image_quality
from app.services.ocr import extract_text
from app.services.llm_parser import parse_document
from app.services.face_match import verify_face


def verify_worker(government_id,
#  selfie, certificate, portfolio
 ):
    
    quality = check_image_quality(government_id)
    if not quality["status"]:
        return {
            "status":"failed",
            "reason":quality["reason"]
        }
    
    ocr_result = extract_text(government_id)

    document = parse_document(ocr_result)

    # face = verify_face(government_id, selfie)

    return {
        "status":"success",
        "document":document,
        # "face":face,
        "quality":quality
    }