from paddleocr import PaddleOCR

# ocr = PaddleOCR(
#     # use_angle_cls=True,
#     lang="en"
# )

ocr = PaddleOCR(
    lang="en",
    device="cpu",
    enable_mkldnn=False
)

def extract_text(image_path: str):
    result = ocr.predict(image_path)
    extracted_text = []
    for page in result:
        if "rec_texts" in page:
            extracted_text.extend(page["rec_texts"])
    return extracted_text