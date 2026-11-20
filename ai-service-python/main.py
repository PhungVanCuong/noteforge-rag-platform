import os
import shutil
import uuid
from typing import List, Optional
from pathlib import Path

from fastapi import FastAPI, UploadFile, File, Form, HTTPException, BackgroundTasks, status, Header
from fastapi.responses import FileResponse
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from pydantic import BaseModel, Field

import config
from converter_service import ConverterService
from rag_service import RagService
from quiz_generator import QuizGenerator
from tts_service import TtsService
from ai_client import generate_with_fallback

app = FastAPI(
    title="NoteForge AI & Document Microservice",
    description="High-performance Python Engine for Document Conversion, Strict Grounded RAG, and AI Quiz Generation",
    version="1.0.0"
)

# CORS Middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=config.ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Mount storage directory for download access
app.mount("/downloads", StaticFiles(directory=config.STORAGE_DIR), name="downloads")

# Initialize services
rag_service = RagService()


# -----------------------------------------------------------------------------
# Pydantic Schemas
# -----------------------------------------------------------------------------
class RagIngestPayload(BaseModel):
    file_path: str
    file_name: str
    notebook_id: int
    document_id: int
    user_id: int
    api_key: Optional[str] = None

class RagChatPayload(BaseModel):
    question: str
    notebook_id: int
    api_key: Optional[str] = None
    top_k: int = Field(default=config.TOP_K, ge=1, le=20)
    similarity_threshold: float = Field(default=config.SIMILARITY_THRESHOLD, ge=0.0, le=1.0)

class QuizGeneratePayload(BaseModel):
    file_path: Optional[str] = None
    raw_text: Optional[str] = None
    api_key: Optional[str] = None
    study_set_id: Optional[int] = None
    callback_url: Optional[str] = None

class TtsSynthesizePayload(BaseModel):
    text: str
    lang: str = "auto"
    voice: Optional[str] = None
    rate: float = 1.0

class TestKeyPayload(BaseModel):
    api_key: str

class MergePdfPayload(BaseModel):
    input_files: List[str]
    output_path: Optional[str] = None

class SplitPdfPayload(BaseModel):
    input_file: str
    page_ranges: str
    output_path: Optional[str] = None

class ConvertImagePayload(BaseModel):
    input_file: str
    output_format: str = "PNG"
    quality: int = 90
    output_path: Optional[str] = None

class ImagesToPdfPayload(BaseModel):
    input_files: List[str]
    output_path: Optional[str] = None

class TranscribePayload(BaseModel):
    media_path: str
    api_key: Optional[str] = None


# -----------------------------------------------------------------------------
# Base Endpoints
# -----------------------------------------------------------------------------
@app.get("/health")
def health_check():
    return {
        "status": "online",
        "service": "NoteForge AI Microservice",
        "vector_store": "ChromaDB (Cosine)",
        "models": {
            "embedding": config.EMBEDDING_MODEL,
            "chat": config.CHAT_MODEL
        }
    }


# -----------------------------------------------------------------------------
# RAG Endpoints
# -----------------------------------------------------------------------------
@app.post("/api/rag/ingest")
def rag_ingest(payload: RagIngestPayload, x_gemini_api_key: Optional[str] = Header(None)):
    try:
        active_key = x_gemini_api_key or payload.api_key
        result = rag_service.ingest_document(
            file_path=payload.file_path,
            file_name=payload.file_name,
            notebook_id=payload.notebook_id,
            document_id=payload.document_id,
            user_id=payload.user_id,
            api_key=active_key
        )
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error during RAG ingestion: {str(e)}"
        )

@app.post("/api/rag/chat")
def rag_chat(payload: RagChatPayload, x_gemini_api_key: Optional[str] = Header(None)):
    try:
        active_key = x_gemini_api_key or payload.api_key
        result = rag_service.query_rag(
            question=payload.question,
            notebook_id=payload.notebook_id,
            api_key=active_key,
            top_k=payload.top_k,
            similarity_threshold=payload.similarity_threshold
        )
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error querying RAG assistant: {str(e)}"
        )

@app.delete("/api/rag/document/{document_id}")
def rag_delete_document(document_id: int):
    try:
        rag_service.delete_document(document_id)
        return {"success": True, "message": f"Document {document_id} removed from vector index."}
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error deleting document vectors: {str(e)}"
        )


# -----------------------------------------------------------------------------
# Quiz & Cloze Generation Endpoint
# -----------------------------------------------------------------------------
@app.post("/api/quiz/generate")
def generate_quiz(payload: QuizGeneratePayload, x_gemini_api_key: Optional[str] = Header(None)):
    try:
        active_key = x_gemini_api_key or payload.api_key
        result = QuizGenerator.generate_full_study_set(
            file_path=payload.file_path,
            raw_text=payload.raw_text,
            api_key=active_key,
            study_set_id=payload.study_set_id,
            callback_url=payload.callback_url
        )
        return {"success": True, "data": result}
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Error generating study set: {str(e)}"
        )


# -----------------------------------------------------------------------------
# Converter Hub Endpoints
# -----------------------------------------------------------------------------
@app.post("/api/convert/pdf-to-word")
def convert_pdf_to_word(input_file: str = Form(...)):
    try:
        out_name = f"{Path(input_file).stem}_{uuid.uuid4().hex[:6]}.docx"
        out_path = os.path.join(config.STORAGE_DIR, "conversions", out_name)
        result_path = ConverterService.pdf_to_word(input_file, out_path)
        download_url = f"/downloads/conversions/{out_name}"
        return {"success": True, "output_path": result_path, "download_url": download_url}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/convert/word-to-pdf")
def convert_word_to_pdf(input_file: str = Form(...)):
    try:
        out_dir = os.path.join(config.STORAGE_DIR, "conversions")
        result_path = ConverterService.word_to_pdf(input_file, out_dir)
        out_name = os.path.basename(result_path)
        download_url = f"/downloads/conversions/{out_name}"
        return {"success": True, "output_path": result_path, "download_url": download_url}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/convert/merge-pdf")
def merge_pdf_endpoint(payload: MergePdfPayload):
    try:
        out_name = f"merged_{uuid.uuid4().hex[:8]}.pdf"
        out_path = payload.output_path or os.path.join(config.STORAGE_DIR, "conversions", out_name)
        result_path = ConverterService.merge_pdfs(payload.input_files, out_path)
        download_url = f"/downloads/conversions/{os.path.basename(result_path)}"
        return {"success": True, "output_path": result_path, "download_url": download_url}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/convert/split-pdf")
def split_pdf_endpoint(payload: SplitPdfPayload):
    try:
        out_name = f"split_{uuid.uuid4().hex[:8]}.pdf"
        out_path = payload.output_path or os.path.join(config.STORAGE_DIR, "conversions", out_name)
        result_path = ConverterService.split_pdf(payload.input_file, out_path, payload.page_ranges)
        download_url = f"/downloads/conversions/{os.path.basename(result_path)}"
        return {"success": True, "output_path": result_path, "download_url": download_url}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/convert/image")
def convert_image_endpoint(payload: ConvertImagePayload):
    try:
        target_fmt = payload.output_format.lower().replace("jpeg", "jpg")
        out_name = f"img_{uuid.uuid4().hex[:8]}.{target_fmt}"
        out_path = payload.output_path or os.path.join(config.STORAGE_DIR, "conversions", out_name)
        result_path = ConverterService.convert_image(
            payload.input_file,
            out_path,
            target_format=payload.output_format,
            quality=payload.quality
        )
        download_url = f"/downloads/conversions/{os.path.basename(result_path)}"
        return {"success": True, "output_path": result_path, "download_url": download_url}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/convert/images-to-pdf")
def images_to_pdf_endpoint(payload: ImagesToPdfPayload):
    try:
        out_name = f"images_{uuid.uuid4().hex[:8]}.pdf"
        out_path = payload.output_path or os.path.join(config.STORAGE_DIR, "conversions", out_name)
        result_path = ConverterService.images_to_pdf(payload.input_files, out_path)
        download_url = f"/downloads/conversions/{os.path.basename(result_path)}"
        return {"success": True, "output_path": result_path, "download_url": download_url}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/convert/transcribe-lecture")
def transcribe_lecture_endpoint(payload: TranscribePayload):
    try:
        transcription_text = ConverterService.transcribe_lecture(
            payload.media_path,
            api_key=payload.api_key
        )
        # Also save to storage as .txt
        txt_name = f"lecture_transcript_{uuid.uuid4().hex[:8]}.txt"
        txt_path = os.path.join(config.STORAGE_DIR, "transcripts", txt_name)
        with open(txt_path, "w", encoding="utf-8") as f:
            f.write(transcription_text)

        download_url = f"/downloads/transcripts/{txt_name}"
        return {
            "success": True,
            "transcription": transcription_text,
            "txt_path": txt_path,
            "download_url": download_url
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# -----------------------------------------------------------------------------
# Text-to-Speech (TTS) Endpoints
# -----------------------------------------------------------------------------
@app.post("/api/tts/synthesize")
async def tts_synthesize_endpoint(payload: TtsSynthesizePayload):
    try:
        res = await TtsService.synthesize_speech(
            text=payload.text,
            lang=payload.lang,
            voice=payload.voice,
            rate=payload.rate
        )
        return {"success": True, "data": res}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/tts/audio/{file_name}")
def get_tts_audio_endpoint(file_name: str):
    file_path = os.path.join(config.STORAGE_DIR, "tts", file_name)
    if not os.path.exists(file_path):
        raise HTTPException(status_code=404, detail="Audio file not found")
    return FileResponse(file_path, media_type="audio/mpeg", filename=file_name)

@app.post("/api/extract-text")
async def extract_text_endpoint(file: UploadFile = File(...)):
    try:
        ext = Path(file.filename).suffix
        safe_name = f"ext_{uuid.uuid4().hex[:8]}{ext}"
        target_path = os.path.join(config.STORAGE_DIR, "uploads", safe_name)
        with open(target_path, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)
        text = QuizGenerator.extract_full_text(target_path)
        return {"success": True, "text": text, "file_name": file.filename}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Không thể trích xuất văn bản: {str(e)}")

@app.post("/api/gemini/test-key")
def test_gemini_key_endpoint(payload: TestKeyPayload):
    try:
        res = generate_with_fallback(
            api_key=payload.api_key,
            prompt="Say 'API_KEY_VALID' in one word."
        )
        return {"success": True, "message": "API Key hợp lệ và hoạt động bình thường!", "response": res.strip()}
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"API Key không hợp lệ hoặc lỗi xác thực: {str(e)}")

# Direct file upload endpoint for frontend testing/convenience
@app.post("/api/upload")
async def upload_direct_file(file: UploadFile = File(...)):
    try:
        ext = Path(file.filename).suffix
        safe_name = f"{uuid.uuid4().hex[:12]}{ext}"
        target_path = os.path.join(config.STORAGE_DIR, "uploads", safe_name)
        with open(target_path, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)
        return {
            "success": True,
            "file_name": file.filename,
            "saved_path": target_path,
            "download_url": f"/downloads/uploads/{safe_name}"
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
