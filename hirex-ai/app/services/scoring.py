def calculate_score(
    quality,
    face,
    document,
    certificate_uploaded
):

    score = 0

    # OCR Success
    document_type = document.get("document_type")
    if document_type and document_type != "Unknown":
        score += 40

    # Face Match
    if face["status"] and face["verified"]:
        score += 40

    # Certificate Bonus
    if certificate_uploaded:
        score += 20
    
    if not face["verified"]:
        status = "resubmit"
    elif score >= 80:
        status = "pending_review"
    else:
        status = "resubmit"

    return score