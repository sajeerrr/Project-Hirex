import cv2


def check_image_quality(image_path: str):

    image = cv2.imread(image_path)

    if image is None:
        return {
            "status": False,
            "reason": "Image could not be read."
        }

    height, width = image.shape[:2]

    # Resolution Check
    if width < 500 or height < 500:
        return {
            "status": False,
            "reason": "Image resolution is too low."
        }

    # Brightness Check
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)

    brightness = gray.mean()

    if brightness < 50:
        return {
            "status": False,
            "reason": "Image is too dark."
        }

    if brightness > 220:
        return {
            "status": False,
            "reason": "Image is overexposed."
        }

    # Blur Check
    blur_score = cv2.Laplacian(gray, cv2.CV_64F).var()

    if blur_score < 100:
        return {
            "status": False,
            "reason": "Image is blurry."
        }

    return {
        "status": True,
        "brightness": round(brightness, 2),
        "blur_score": round(blur_score, 2),
        "width": width,
        "height": height
    }