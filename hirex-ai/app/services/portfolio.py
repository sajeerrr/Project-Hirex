# import torch
# import open_clip

# from PIL import Image

# model, _, preprocess = open_clip.create_model_and_transforms(
#     "ViT-B-32",
#     pretrained="laion2b_s34b_b79k"
# )

# tokenizer = open_clip.get_tokenizer("ViT-B-32")

# PROFESSION_PROMPTS = {

#     "Electrician":
#         "an electrician repairing electrical wiring",

#     "Plumber":
#         "a plumber repairing water pipes",

#     "Painter":
#         "a person painting a house",

#     "Carpenter":
#         "a carpenter building furniture",

#     "Mechanic":
#         "a vehicle mechanic repairing an engine"
# }

# def analyze_image(image_path, profession):

#     image = preprocess(
#         Image.open(image_path)
#     ).unsqueeze(0)

#     text = tokenizer([
#         PROFESSION_PROMPTS[profession]
#     ])

#     with torch.no_grad():

#         image_features = model.encode_image(image)

#         text_features = model.encode_text(text)

#         similarity = (
#             image_features @ text_features.T
#         ).item()

#     return similarity