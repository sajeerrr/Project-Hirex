# HireX

A web-based platform for finding and hiring verified contractors and skilled workers across cities, powered by an AI-driven identity and document verification system.

| Resource | Link |
|----------|------|
| Live Application | https://hirex.infinityfree.me |
| Platform Demo | https://youtu.be/FH4M3a2kN6A |
| AI Verification Demo | https://youtu.be/bFwtgBgoU_g |
---

## Overview

HireX connects users with skilled service professionals in urban environments. Workers are searchable by location, specialization, availability, and ratings. To address trust and fraud challenges common in online service marketplaces, HireX includes an AI-powered verification pipeline that analyzes government IDs, selfies, and certificates — generating a structured verification score for admin review.

---

## Tech Stack

| Layer              | Technology                                          |
|--------------------|-----------------------------------------------------|
| Frontend           | HTML5, CSS3, Bootstrap, JavaScript                  |
| Backend            | PHP                                                 |
| Database           | MySQL                                               |
| AI Backend         | FastAPI (Python)                                    |
| OCR Engine         | PaddleOCR                                           |
| Computer Vision    | OpenCV                                              |
| Face Verification  | DeepFace (ArcFace + RetinaFace)                     |
| LLM Integration    | LangChain + Groq (Llama 3.3 70B)                   |
| API Validation     | Pydantic                                            |
| Communication      | REST API (PHP to FastAPI)                           |

---

## Architecture

The platform follows a microservices architecture. The PHP web application handles user interactions and delegates all AI-related processing to a standalone FastAPI service.

```
Worker
  |
  v
PHP Web Application
  |
  | Uploads: Government ID, Selfie, Certificate
  v
FastAPI Verification API
  |
  |-- Image Quality Analysis (OpenCV)
  |-- OCR (PaddleOCR)
  |-- LLM Document Parsing (LangChain + Groq)
  |-- Face Verification (DeepFace)
  |-- Score Calculation
  |
  v
JSON Response
  |
  v
PHP Application --> MySQL Database
  |
  v
Admin Review --> Approve / Reject / Request Resubmission
```

---

## Modules

### User
- Register, log in, and search for services
- View worker and contractor profiles
- Initiate communication and submit feedback

### Worker / Contractor
- Manage profile and post service details
- Respond to customer queries
- View ratings and feedback

### Admin
- Review AI-generated verification reports
- Approve, reject, or request resubmission from workers
- Monitor platform activity and maintain data integrity

---

## AI Verification System

### 1. Image Quality Check

Before OCR is attempted, each uploaded document is analyzed for quality using OpenCV.

| Check               | Method                  |
|---------------------|-------------------------|
| Blur Detection      | Laplacian Variance      |
| Brightness          | Mean Pixel Value        |
| Resolution          | Minimum threshold check |

Documents that fail quality checks are rejected before further processing.

### 2. OCR — Text Extraction

PaddleOCR extracts text from government-issued identity documents. It supports rotated documents, multiple layouts, and delivers high inference speed.

Supported documents: Aadhaar, PAN Card, Driving License (Passport — planned)

### 3. LLM Document Parsing

Raw OCR output is unstructured text. A Groq-hosted Llama 3.3 70B model, orchestrated via LangChain, converts that text into structured JSON without relying on regex patterns. This approach supports multiple document types and is easily extensible.

```json
{
  "document_type": "Aadhaar",
  "name": "Rahul Kumar",
  "dob": "12/05/1998",
  "gender": "Male",
  "id_number": "XXXX XXXX 1234"
}
```

### 4. Face Verification

DeepFace compares the government ID photo against the uploaded selfie using the ArcFace model with RetinaFace as the face detector. A match confidence score is returned.

### 5. Verification Score

All AI outputs are combined into a single score used during admin review.

| Component      | Weight |
|----------------|--------|
| OCR Accuracy   | 40%    |
| Face Match     | 40%    |
| Certificate    | 20%    |

### 6. Admin Review

AI does not make the final decision. Every verification result is queued for admin review. Admins can approve, reject, or request resubmission. This ensures no worker is automatically approved based solely on AI output.

---

## API

**Endpoint:** `POST /api/verify-worker`

**Accepts:** Government ID, Selfie, Certificate (multipart form data)

**Returns:**
```json
{
  "success": true,
  "message": "Verification completed.",
  "data": {
    "verification": {
      "score": 95,
      "ocr_status": "success",
      "face_verified": true,
      "face_match_score": 96
    },
    "document": {
      "document_type": "Aadhaar",
      "name": "Rahul Kumar"
    }
  }
}
```

---

## Project Structure

```
hirex-ai/
  app/
    main.py
    startup.py
    routes/
      verification.py
    services/
      verification_engine.py
      ocr.py
      face_match.py
      llm_parser.py
      image_quality.py
      scoring.py
    schemas/
      response.py
    utils/
      file_handler.py
  uploads/
```

---

## Security

| Measure                    | Implementation                          |
|----------------------------|-----------------------------------------|
| Password Hashing           | PHP `password_hash()`                   |
| SQL Injection Protection   | Prepared statements (MySQLi / PDO)      |
| Filename Safety            | UUID-based filenames on upload          |
| File Validation            | Server-side extension and type checks   |
| Document Quality Gate      | Rejects low-quality images pre-OCR      |

---

## Deployment

```
PHP Application (InfinityFree)
  |
  v
FastAPI AI Backend (Render / Oracle Cloud)
  |
  v
Groq API (LLM inference)
  |
  v
JSON Response --> MySQL
```

The PHP frontend and the FastAPI AI backend are deployed independently, allowing each layer to be scaled or updated without affecting the other.

---

## Planned Enhancements

- Portfolio image verification using OpenCLIP
- AI-generated skill assessment questions for workers
- Duplicate worker detection
- AI-generated risk scoring
- Fake document detection
- Liveness detection for selfies
- Face anti-spoofing
- Multi-language OCR support
- Automated email and SMS notifications
- Support for additional identity document types

---

## Summary

HireX modernizes urban service hiring by combining a user-facing web platform with a production-style AI verification backend. The system reduces the burden of manual document review while keeping human administrators in control of every final decision — making it both practical and trustworthy for real-world deployment.
