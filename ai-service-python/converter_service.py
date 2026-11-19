import os
import subprocess
import shutil
from pathlib import Path
from typing import List, Optional
from PIL import Image
import fitz  # PyMuPDF
from pdf2docx import Converter
import google.generativeai as genai

import config

class ConverterService:
    @staticmethod
    def pdf_to_word(input_pdf: str, output_docx: str) -> str:
        """Convert PDF to Word (.docx) preserving layout and tables."""
        if not os.path.exists(input_pdf):
            raise FileNotFoundError(f"Input file not found: {input_pdf}")
        
        os.makedirs(os.path.dirname(output_docx), exist_ok=True)
        cv = Converter(input_pdf)
        try:
            cv.convert(output_docx, start=0, end=None)
        finally:
            cv.close()
            
        return output_docx

    @staticmethod
    def word_to_pdf(input_file: str, output_dir: str) -> str:
        """Convert Word/PPTX/Excel to PDF using LibreOffice Headless."""
        if not os.path.exists(input_file):
            raise FileNotFoundError(f"Input file not found: {input_file}")
            
        os.makedirs(output_dir, exist_ok=True)
        
        # Check for LibreOffice binary
        soffice_cmd = None
        for candidate in ["soffice", "libreoffice", r"C:\Program Files\LibreOffice\program\soffice.exe"]:
            if shutil.which(candidate) or os.path.exists(candidate):
                soffice_cmd = candidate
                break
                
        if not soffice_cmd:
            raise RuntimeError(
                "LibreOffice (soffice) is not installed or not found in system PATH. "
                "Please install LibreOffice for DOCX/PPTX to PDF conversion."
            )
            
        cmd = [
            soffice_cmd,
            "--headless",
            "--convert-to", "pdf",
            "--outdir", output_dir,
            input_file
        ]
        
        result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=120)
        if result.returncode != 0:
            raise RuntimeError(f"LibreOffice conversion failed: {result.stderr}")
            
        stem = Path(input_file).stem
        output_pdf = os.path.join(output_dir, f"{stem}.pdf")
        if not os.path.exists(output_pdf):
            raise RuntimeError(f"Output PDF not generated at {output_pdf}")
            
        return output_pdf

    @staticmethod
    def merge_pdfs(input_pdf_list: List[str], output_pdf: str) -> str:
        """Merge multiple PDF files in specified order using PyMuPDF."""
        if not input_pdf_list:
            raise ValueError("No PDF files provided for merging.")
            
        os.makedirs(os.path.dirname(output_pdf), exist_ok=True)
        merged_doc = fitz.open()
        
        for file_path in input_pdf_list:
            if not os.path.exists(file_path):
                raise FileNotFoundError(f"PDF file not found: {file_path}")
            with fitz.open(file_path) as doc:
                merged_doc.insert_pdf(doc)
                
        merged_doc.save(output_pdf, garbage=4, deflate=True)
        merged_doc.close()
        return output_pdf

    @staticmethod
    def split_pdf(input_pdf: str, output_pdf: str, page_ranges: str) -> str:
        """
        Split or extract pages from a PDF.
        page_ranges format: '1-3,5,8-10' (1-based index).
        """
        if not os.path.exists(input_pdf):
            raise FileNotFoundError(f"Input file not found: {input_pdf}")
            
        os.makedirs(os.path.dirname(output_pdf), exist_ok=True)
        source_doc = fitz.open(input_pdf)
        dest_doc = fitz.open()
        total_pages = len(source_doc)
        
        selected_pages = set()
        parts = [p.strip() for p in page_ranges.split(",") if p.strip()]
        for part in parts:
            if "-" in part:
                start_str, end_str = part.split("-", 1)
                start = max(1, int(start_str))
                end = min(total_pages, int(end_str))
                for page_idx in range(start, end + 1):
                    selected_pages.add(page_idx - 1)
            else:
                p = int(part)
                if 1 <= p <= total_pages:
                    selected_pages.add(p - 1)
                    
        for page_idx in sorted(selected_pages):
            dest_doc.insert_pdf(source_doc, from_page=page_idx, to_page=page_idx)
            
        dest_doc.save(output_pdf, garbage=4, deflate=True)
        dest_doc.close()
        source_doc.close()
        return output_pdf

    @staticmethod
    def convert_image(input_img: str, output_img: str, target_format: str = "PNG", quality: int = 90) -> str:
        """Convert image format (PNG, JPG, WEBP) and adjust compression quality."""
        if not os.path.exists(input_img):
            raise FileNotFoundError(f"Input image not found: {input_img}")
            
        os.makedirs(os.path.dirname(output_img), exist_ok=True)
        with Image.open(input_img) as im:
            fmt = target_format.upper()
            if fmt in ["JPG", "JPEG"]:
                fmt = "JPEG"
                if im.mode in ("RGBA", "LA", "P"):
                    im = im.convert("RGB")
            im.save(output_img, format=fmt, quality=quality)
            
        return output_img

    @staticmethod
    def images_to_pdf(input_img_list: List[str], output_pdf: str) -> str:
        """Combine multiple images into a single multi-page PDF."""
        if not input_img_list:
            raise ValueError("No images provided for PDF conversion.")
            
        os.makedirs(os.path.dirname(output_pdf), exist_ok=True)
        pil_images = []
        for img_path in input_img_list:
            if not os.path.exists(img_path):
                raise FileNotFoundError(f"Image not found: {img_path}")
            im = Image.open(img_path)
            if im.mode != "RGB":
                im = im.convert("RGB")
            pil_images.append(im)
            
        first_img = pil_images[0]
        remaining = pil_images[1:] if len(pil_images) > 1 else []
        first_img.save(output_pdf, "PDF", resolution=150.0, save_all=True, append_images=remaining)
        return output_pdf

    @staticmethod
    def extract_audio(input_media: str, output_audio: str) -> str:
        """Extract audio stream from video/audio to 16kHz mono MP3 via FFmpeg."""
        if not os.path.exists(input_media):
            raise FileNotFoundError(f"Input media not found: {input_media}")
            
        os.makedirs(os.path.dirname(output_audio), exist_ok=True)
        cmd = [
            "ffmpeg", "-y",
            "-i", input_media,
            "-vn",
            "-acodec", "libmp3lame",
            "-ar", "16000",
            "-ac", "1",
            "-b:a", "128k",
            output_audio
        ]
        
        result = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=300)
        if result.returncode != 0:
            raise RuntimeError(f"FFmpeg audio extraction failed: {result.stderr}")
            
        return output_audio

    @classmethod
    def transcribe_lecture(cls, media_path: str, api_key: Optional[str] = None) -> str:
        """
        Transcribe lecture audio/video using FFmpeg and Gemini Multimodal API.
        Extracts speech with timestamp markers [MM:SS].
        """
        active_key = api_key or config.GEMINI_API_KEY
        if not active_key:
            raise ValueError("Gemini API key is required for lecture transcription.")
            
        genai.configure(api_key=active_key)
        
        # If input is a video or large container, extract audio first
        ext = Path(media_path).suffix.lower()
        if ext in [".mp4", ".mkv", ".avi", ".mov", ".webm", ".flv"]:
            audio_out = os.path.join(config.STORAGE_DIR, "conversions", f"{Path(media_path).stem}_extracted.mp3")
            target_file = cls.extract_audio(media_path, audio_out)
        else:
            target_file = media_path

        # Upload file to Gemini Files API
        uploaded_file = genai.upload_file(path=target_file)
        
        model = genai.GenerativeModel(config.CHAT_MODEL)
        prompt = (
            "Bạn là chuyên gia bóc băng bài giảng học thuật. Hãy nghe kỹ toàn bộ tệp âm thanh này "
            "và chuyển thể chính xác 100% nội dung giảng viên/người thuyết trình sang tiếng Việt văn bản.\n\n"
            "YÊU CẦU ĐẶC BIỆT:\n"
            "1. Thêm mốc thời gian chi tiết theo định dạng [MM:SS] ở đầu mỗi ý hoặc đoạn quan trọng.\n"
            "2. Trình bày khoa học với các đề mục, gạch đầu dòng, từ khóa chuyên ngành rõ ràng.\n"
            "3. Giữ nguyên thuật ngữ kỹ thuật, tên biến hoặc công thức được nhắc tới.\n"
            "4. Văn phong chuẩn mực sư phạm, dễ dàng đưa vào làm giáo trình ôn tập RAG."
        )
        
        response = model.generate_content([uploaded_file, prompt])
        
        # Cleanup uploaded remote file
        try:
            genai.delete_file(uploaded_file.name)
        except Exception:
            pass
            
        return response.text
