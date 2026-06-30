from paddleocr import PaddleOCR
from deepface import DeepFace
from langchain_groq import ChatGroq
import os
from dotenv import load_dotenv
from app.schemas.document import DocumentSchema

load_dotenv()

class AIModels:

    ocr = None
    llm = None

    @classmethod
    def initialize(cls):
        print("Loading PaddleOCR...")
        cls.ocr = PaddleOCR(
            lang="en",
            device="cpu",
            enable_mkldnn=False
        )

        print("Loading Groq Client...")

        cls.llm = ChatGroq(
            model="llama-3.3-70b-versatile",
            api_key=os.getenv("GROQ_API_KEY")
        )

        print("Creating Structured LLM...")
        cls.structured_llm = cls.llm.with_structured_output(DocumentSchema)

        print("AI Models Loaded")