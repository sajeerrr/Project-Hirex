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

        distance = round(result["distance"], 4)
        threshold = result["threshold"]

        # match_percentage = max(
        #     0,
        #     min(
        #         100,
        #         round((1 - (distance / threshold)) * 100, 2)
        #     )
        # )
        match_percentage = max(
            0,
            round((1 - distance) * 100)
        )

        return {
            "status": True,
            "verified": result["verified"],
            "distance": distance,
            "threshold": threshold,
            "match_percentage": match_percentage
        }

    except Exception as e:
        return {
            "status": False,
            "verified": False,
            "distance": None,
            "threshold": None,
            "match_percentage": 0,
            "message": str(e)
        }