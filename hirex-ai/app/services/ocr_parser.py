import re


def parse_ocr_data(texts):

    full_text = " ".join(texts)

    parsed = {
        "document_type": "Unknown",
        "name": None,
        "id_number": None,
        "dob": None,
        "gender": None
    }

    # Aadhaar
    if "Government of India" in full_text:
        parsed["document_type"] = "Aadhaar"

    # Gender
    if "Male" in full_text:
        parsed["gender"] = "Male"

    elif "Female" in full_text:
        parsed["gender"] = "Female"

    # Date of Birth
    dob = re.search(
        r"\d{2}/\d{2}/\d{4}",
        full_text
    )

    if dob:
        parsed["dob"] = dob.group()

    # Aadhaar Number
    aadhaar = re.search(
        r"\d{4}\s\d{4}\s\d{4}",
        full_text
    )

    if aadhaar:
        parsed["id_number"] = aadhaar.group()

    return parsed