from paddleocr import PaddleOCR
from app.startup import AIModels

# ocr = PaddleOCR(
#     lang="en",
#     device="cpu",
#     enable_mkldnn=False
# )

def extract_text(image_path: str):
    result = AIModels.ocr.predict(image_path)
    extracted_text = []
    for page in result:
        if "rec_texts" in page:
            extracted_text.extend(page["rec_texts"])
    return extracted_text