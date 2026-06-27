from deepface import DeepFace

def verify_face(id_image, selfie_image):
    try:
        result = DeepFace.verify(
            img1_path=id_image,
            img2_path=selfie_image,
            model_name="ArcFace",
            detector_backend="retinaface",
            enforce_detection=True
        )

        return {
            "status": True,
            "verified": result["verified"],
            "distance": round(result["distance"], 4),
            "threshold": result["threshold"]
        }

    except Exception as e:
        return {
            "status": False,
            "message": str(e)
        }