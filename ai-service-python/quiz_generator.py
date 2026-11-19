import json
import re
import logging
from typing import Dict, Any, List, Optional
from pathlib import Path

import fitz  # PyMuPDF
from docx import Document as DocxDocument
import google.generativeai as genai

try:
    from . import config
    from .ai_client import generate_with_fallback, clean_json_string
except ImportError:
    import config
    from ai_client import generate_with_fallback, clean_json_string

logger = logging.getLogger(__name__)

QUIZ_GENERATOR_PROMPT = """Bạn là chuyên gia biên soạn đề thi và hệ thống hóa học liệu tương tác. Hãy phân tích đoạn nội dung tài liệu được cung cấp dưới đây và chuyển hóa TOÀN BỘ kiến thức/câu hỏi thành định dạng JSON chuẩn xác.

YÊU CẦU XỬ LÝ:
1. Nếu tài liệu đầu vào đã có sẵn danh sách câu hỏi trắc nghiệm (A, B, C, D hoặc 1, 2, 3, 4): Hãy trích xuất ĐẦY ĐỦ 100% số câu hỏi có trong tài liệu, KHÔNG ĐƯỢC BỎ SÓT BẤT KỲ CÂU NÀO.
   - Xác định chính xác đáp án đúng ('ans' là chỉ số 0 cho A, 1 cho B, 2 cho C, 3 cho D).
   - Nếu tài liệu có giải thích đáp án, hãy giữ lại. Nếu thiếu lời giải thích, hãy tự viết phần giải thích 'ex' ngắn gọn, dễ hiểu và chính xác.
2. Nếu tài liệu là giáo trình lý thuyết hoặc mã nguồn: Hãy tự biên soạn ít nhất 10-25 câu hỏi trắc nghiệm bao phủ toàn bộ kiến thức quan trọng trong đoạn văn bản.
3. Đối với phần "code_exercises" (Bài tập điền khuyết / Cloze Code): Hãy tìm các đoạn mã nguồn (C#, Python, JavaScript, SQL, HTML/CSS...) hoặc các công thức, câu lệnh định nghĩa quan trọng. Đặt cặp ngoặc vuông kép [[từ_khóa]] bao quanh các từ khóa cốt lõi cần đục lỗ để sinh viên gõ điền khuyết (từ 3 đến 6 ô trống trong mỗi đoạn code).
4. Chỉ trả về duy nhất một chuỗi JSON hợp lệ, không kèm văn bản markdown ngoài ```json.

CẤU TRÚC JSON BẮT BUỘC:
{{
  "categories": ["Tên chủ đề 1", "Tên chủ đề 2"],
  "questions": [
    {{
      "id": "Q1",
      "cat": 0,
      "en": "Nội dung câu hỏi đầy đủ?",
      "opts": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
      "ans": 0,
      "ex": "Giải thích chi tiết tại sao đáp án này đúng."
    }}
  ],
  "code_exercises": [
    {{
      "title": "Tên bài tập code / chức năng",
      "code": "public class User : [[IEntity]] \\n{{\\n    public int Id {{ get; set; }}\\n}}"
    }}
  ]
}}

[NỘI DUNG TÀI LIỆU ĐẦU VÀO]:
{document_chunk_text}"""


class QuizGenerator:
    @staticmethod
    def extract_full_text(file_path: str) -> str:
        """Extract full plain text from PDF, DOCX (including tables), or TXT."""
        path = Path(file_path)
        ext = path.suffix.lower()

        if ext == ".pdf":
            doc = fitz.open(file_path)
            pages = []
            for page in doc:
                text = page.get_text("text").strip()
                if text:
                    pages.append(text)
            doc.close()
            return "\n\n".join(pages)

        elif ext in [".docx", ".doc"]:
            try:
                doc = DocxDocument(file_path)
                parts = []
                # Extract paragraphs
                for p in doc.paragraphs:
                    t = p.text.strip()
                    if t:
                        parts.append(t)
                # Extract all tables
                for table in doc.tables:
                    for row in table.rows:
                        row_vals = [cell.text.strip() for cell in row.cells if cell.text.strip()]
                        # Deduplicate identical adjacent cells (due to merged cells)
                        unique_vals = []
                        for v in row_vals:
                            if not unique_vals or unique_vals[-1] != v:
                                unique_vals.append(v)
                        if unique_vals:
                            parts.append(" | ".join(unique_vals))
                return "\n\n".join(parts)
            except Exception as e:
                logger.error(f"Error parsing docx with python-docx: {e}")
                # Fallback to plain binary read if docx fails
                with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                    return f.read()

        elif ext in [".txt", ".md"]:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                return f.read()

        raise ValueError(f"Định dạng tệp không được hỗ trợ: {ext}")

    @classmethod
    def generate_quiz_chunk(
        cls,
        text_chunk: str,
        api_key: Optional[str] = None
    ) -> Dict[str, Any]:
        """Generate quiz JSON for a single text chunk using Gemini with fallback."""
        prompt = QUIZ_GENERATOR_PROMPT.format(document_chunk_text=text_chunk)
        generation_config = {
            "response_mime_type": "application/json",
            "temperature": 0.2,
        }

        raw_response = generate_with_fallback(
            api_key=api_key,
            prompt=prompt,
            generation_config=generation_config
        )

        return clean_json_string(raw_response)

    @classmethod
    def generate_full_study_set(
        cls,
        file_path: Optional[str] = None,
        raw_text: Optional[str] = None,
        api_key: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Processes document or text, handles multi-batch chunking if large (8,000 chars/batch),
        and aggregates into a single standardized Study Set schema.
        """
        extracted_text = ""
        if file_path:
            extracted_text = cls.extract_full_text(file_path)

        if raw_text and raw_text.strip():
            if extracted_text:
                extracted_text = extracted_text + "\n\n=== NỘI DUNG BỔ SUNG TỪ NGƯỜI DÙNG ===\n\n" + raw_text.strip()
            else:
                extracted_text = raw_text.strip()

        if not extracted_text.strip():
            raise ValueError("Không tìm thấy nội dung văn bản nào để trích xuất trong tệp hoặc ghi chú.")

        text = extracted_text.strip()

        # Batch chunking (approx 8,000 characters per batch for high accuracy)
        max_batch_chars = 8000
        if len(text) <= max_batch_chars:
            batches = [text]
        else:
            paragraphs = text.split("\n\n")
            batches = []
            current_batch = []
            current_len = 0

            for p in paragraphs:
                p_len = len(p)
                if current_len + p_len > max_batch_chars and current_batch:
                    batches.append("\n\n".join(current_batch))
                    current_batch = [p]
                    current_len = p_len
                else:
                    current_batch.append(p)
                    current_len += p_len

            if current_batch:
                batches.append("\n\n".join(current_batch))

        logger.info(f"Splitting document ({len(text)} chars) into {len(batches)} batches for AI processing...")

        combined_categories: List[str] = []
        cat_index_map: Dict[str, int] = {}
        combined_questions: List[Dict[str, Any]] = []
        combined_code_exercises: List[Dict[str, Any]] = []

        last_batch_error = None
        for b_idx, batch_text in enumerate(batches):
            try:
                logger.info(f"Processing batch {b_idx + 1}/{len(batches)}...")
                chunk_data = cls.generate_quiz_chunk(batch_text, api_key=api_key)
                chunk_cats = chunk_data.get("categories", [])

                # Map local cat index to merged cat index
                local_to_global_cat = {}
                for local_idx, cat_name in enumerate(chunk_cats):
                    clean_cat = str(cat_name).strip()
                    if clean_cat not in cat_index_map:
                        new_idx = len(combined_categories)
                        cat_index_map[clean_cat] = new_idx
                        combined_categories.append(clean_cat)
                    local_to_global_cat[local_idx] = cat_index_map[clean_cat]

                # Adjust questions
                for q in chunk_data.get("questions", []):
                    old_cat = q.get("cat", 0)
                    q["cat"] = local_to_global_cat.get(old_cat, 0)
                    combined_questions.append(q)

                # Append code exercises
                combined_code_exercises.extend(chunk_data.get("code_exercises", []))
            except Exception as e:
                logger.error(f"Error processing batch {b_idx + 1}: {e}")
                last_batch_error = e
                continue

        # Check if empty
        if not combined_questions and not combined_code_exercises:
            raise ValueError(
                f"Gemini AI không trích xuất được câu hỏi nào từ tài liệu. Chi tiết lỗi: {last_batch_error or 'Dữ liệu trả về rỗng'}"
            )

        # Re-index questions sequentially
        for idx, q in enumerate(combined_questions):
            q["id"] = f"Q{idx + 1}"

        if not combined_categories:
            combined_categories = ["Tổng hợp kiến thức"]

        return cls._standardize_data({
            "categories": combined_categories,
            "questions": combined_questions,
            "code_exercises": combined_code_exercises
        })

    @staticmethod
    def _standardize_data(data: Dict[str, Any]) -> Dict[str, Any]:
        """Ensure IDs, types, and categories are properly formatted."""
        categories = data.get("categories", ["Kiến thức chung"])
        questions = data.get("questions", [])
        code_exercises = data.get("code_exercises", [])

        valid_questions = []
        for idx, q in enumerate(questions):
            if not isinstance(q, dict):
                continue
            en = q.get("en", "").strip()
            opts = q.get("opts", [])
            if not en or not isinstance(opts, list) or len(opts) < 2:
                continue

            cat_idx = q.get("cat", 0)
            if not isinstance(cat_idx, int) or cat_idx < 0 or cat_idx >= len(categories):
                cat_idx = 0

            ans_idx = q.get("ans", 0)
            if not isinstance(ans_idx, int) or ans_idx < 0 or ans_idx >= len(opts):
                ans_idx = 0

            ex = q.get("ex", "Đáp án chính xác theo tài liệu.")

            valid_questions.append({
                "id": f"Q{len(valid_questions) + 1}",
                "cat": cat_idx,
                "en": en,
                "opts": [str(o) for o in opts],
                "ans": ans_idx,
                "ex": str(ex)
            })

        valid_code_exercises = []
        for idx, c in enumerate(code_exercises):
            if not isinstance(c, dict):
                continue
            code_text = c.get("code", "").strip()
            title = c.get("title", f"Bài tập thực hành {idx + 1}").strip()
            if code_text:
                valid_code_exercises.append({
                    "title": title,
                    "code": code_text
                })

        return {
            "categories": categories,
            "questions": valid_questions,
            "code_exercises": valid_code_exercises
        }
