from pydantic import BaseModel


class DocumentSchema(BaseModel):
    document_type: str | None = None
    name: str | None = None
    id_number: str | None = None
    dob: str | None = None
    gender: str | None = None