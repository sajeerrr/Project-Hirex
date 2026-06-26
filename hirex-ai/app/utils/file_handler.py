import os
import uuid
import shutil
from fastapi import UploadFile

ALLOWED_IMAGE_TYPES = ["jpg", "jpeg", "png", "avif"]
ALLOWED_DOCUMENT_TYPES = ["jpg", "jpeg", "png", "pdf", "avif"]


def save_file(file: UploadFile, folder: str, allowed_extensions: list):

    extension = file.filename.split(".")[-1].lower()

    if extension not in allowed_extensions:
        raise ValueError(f"Invalid file type: {extension}")

    filename = f"{uuid.uuid4()}.{extension}"

    filepath = os.path.join("uploads", folder, filename)

    os.makedirs(os.path.dirname(filepath), exist_ok=True)

    with open(filepath, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    return filepath