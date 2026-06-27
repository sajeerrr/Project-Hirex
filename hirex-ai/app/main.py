from fastapi import FastAPI
from app.routes.verification import router as verification_router
from app.startup import AIModels

app=FastAPI(
    title="Hirex",
    version="1.0.0"
)

AIModels.initialize()

app.include_router(
    verification_router,
    prefix="/api",
    tags=["Verification"]
)

@app.get("/")
def home():
    return {
        "message":"Hirex is running"
    }