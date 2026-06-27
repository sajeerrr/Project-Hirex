def calculate_score(
    quality,
    face,
    document,
    certificate_uploaded
):

    score = 0

    # OCR Success
    if document.get("document_type") != "Unknown":
        score += 40

    # Face Match
    if face["status"] and face["verified"]:
        score += 40

    # Certificate Bonus
    if certificate_uploaded:
        score += 20

    return score