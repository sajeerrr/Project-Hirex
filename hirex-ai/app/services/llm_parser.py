import os
import json
from dotenv import load_dotenv
from langchain_groq import ChatGroq
from langchain_core.prompts import ChatPromptTemplate
from app.schemas.document import DocumentSchema

load_dotenv()

llm = ChatGroq(
    model="llama-3.3-70b-versatile",
    api_key=os.getenv("GROQ_API_KEY")
)

structured_llm = llm.with_structured_output(DocumentSchema)

PROMPT = """
You are an expert in government identity documents.

Analyze the OCR text and determine the document type.

Possible document types include:
- Aadhaar Card
- PAN Card
- Passport
- Driving Licence
- Voter ID
- Government ID

If you are reasonably confident, return the detected document type.

If there is insufficient information, return null.

OCR Text:

{text}
"""

prompt = ChatPromptTemplate.from_template(PROMPT)

# def parse_document(ocr_text):
#     chain = prompt | llm
#     response = chain.invoke({
#         "text":"\n".join(ocr_text)
#     })

#     try:
#         return json.loads(response.content)
#     except:
#         return {
#             "error":"Invalid JSON",
#             "raw":response.content
#         }

def parse_document(ocr_text):
    formatted_prompt = prompt.invoke({
        "text": "\n".join(ocr_text)
    })

    result = structured_llm.invoke(formatted_prompt)

    return result.model_dump()