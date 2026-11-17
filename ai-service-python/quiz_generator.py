import json
import re
from typing import Dict, Any, List, Optional
from pathlib import Path

import fitz  # PyMuPDF
from docx import Document as DocxDocument
import google.generativeai as genai

import config

QUIZ_GENERATOR_PROMPT = """Bạn là chuyên gia biên soạn đề thi và hệ thống hóa học liệu tương tác. Hãy phân tích đoạn nội dung tài liệu được cung cấp dưới đây và chuyển hóa TOÀN BỘ kiến thức/câu hỏi thành định dạng JSON chuẩn xác.

YÊU CẦU XỬ LÝ:
1. Nếu tài liệu đầu vào đã có sẵn danh sách câu hỏi trắc nghiệm: Hãy trích xuất ĐẦY ĐỦ 100% số câu hỏi có trong đoạn văn bản, KHÔNG được bỏ sót bất kỳ câu nào. Nếu thiếu lời giải thích, hãy tự viết giải thích ngắn gọn, chính xác dựa trên kiến thức chuyên ngành.
2. Nếu tài liệu đầu vào là giáo trình lý thuyết hoặc mã nguồn (code): Hãy tự biên soạn các câu hỏi trắc nghiệm bao phủ toàn bộ các khái niệm cốt lõi, đồng thời trích xuất các đoạn code mẫu hoặc định nghĩa/công thức quan trọng thành bài tập điền khuyết.
3. Đối với phần "code_exercises" (Bài tập điền khuyết): Đặt cặp dấu ngoặc vuông kép [[từ_khóa]] bao quanh từ khóa, tên hàm, thuộc tính hoặc thuật ngữ quan trọng nhất mà sinh viên cần ghi nhớ (từ 3 đến 6 ô trống mỗi đoạn).
4. Chỉ trả về duy nhất một đối tượng JSON hợp lệ, không kèm văn bản giải thích bên ngoài hay markdown ```json.

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
      "ex": "Giải thích chi tiết tại sao đáp án này đúng (kèm số trang tài liệu nếu có)."
    }}
  ],
  "code_exercises": [
    {{
      "title": "Tên bài tập thực hành / đoạn code",
      "code": "Đoạn code hoặc công thức có chứa các từ khóa được bọc bởi [[từ_khóa]]"
    }}
  ]
}}

[NỘI DUNG TÀI LIỆU ĐẦU VÀO]:
{document_chunk_text}"""


class QuizGenerator:
    @staticmethod
    def extract_full_text(file_path: str) -> str:
        """Extract full plain text from PDF, DOCX, or TXT."""
        ext = Path(file_path).suffix.lower()
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
            doc = DocxDocument(file_path)
            paragraphs = [p.text.strip() for p in doc.paragraphs if p.text.strip()]
            return "\n\n".join(paragraphs)

        elif ext in [".txt", ".md"]:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                return f.read()

        raise ValueError(f"Unsupported file format: {ext}")

    @classmethod
    def clean_json_response(cls, raw_text: str) -> Dict[str, Any]:
        """Strip markdown ticks and parse JSON safely."""
        text = raw_text.strip()
        if text.startswith("```"):
            text = re.sub(r"^```(?:json)?\s*", "", text)
            text = re.sub(r"\s*```$", "", text)
        return json.loads(text.strip())

    @classmethod
    def generate_quiz_chunk(
        cls,
        text_chunk: str,
        api_key: Optional[str] = None
    ) -> Dict[str, Any]:
        """Generate quiz JSON for a single text chunk using Gemini 2.5 Flash."""
        active_key = api_key or config.GEMINI_API_KEY
        if not active_key:
            raise ValueError("Gemini API key is required to generate quiz.")

        genai.configure(api_key=active_key)
        model = genai.GenerativeModel(
            model_name=config.CHAT_MODEL,
            generation_config={"response_mime_type": "application/json"}
        )

        prompt = QUIZ_GENERATOR_PROMPT.format(document_chunk_text=text_chunk)
        response = model.generate_content(prompt)

        return cls.clean_json_response(response.text)

    @classmethod
    def generate_full_study_set(
        cls,
        file_path: Optional[str] = None,
        raw_text: Optional[str] = None,
        api_key: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Processes document or text, handles multi-batch chunking if large,
        and aggregates into a single standardized Study Set schema.
        """
        if file_path:
            text = cls.extract_full_text(file_path)
        elif raw_text:
            text = raw_text
        else:
            raise ValueError("Either file_path or raw_text must be provided.")

        if not text.strip():
            raise ValueError("Document contains no readable text.")

        # If document is small or medium (under 12,000 characters), process in one shot
        max_batch_chars = 10000
        if len(text) <= max_batch_chars:
            data = cls.generate_quiz_chunk(text, api_key=api_key)
            return cls._standardize_data(data)

        # Split large text into batches of approx max_batch_chars
        paragraphs = text.split("\n\n")
        batches = []
        current_batch = []
        current_len = 0

        for p in paragraphs:
            if current_len + len(p) > max_batch_chars and current_batch:
                batches.append("\n\n".join(current_batch))
                current_batch = [p]
                current_len = len(p)
            else:
                current_batch.append(p)
                current_len += len(p)

        if current_batch:
            batches.append("\n\n".join(current_batch))

        # Process each batch and merge
        combined_categories = []
        cat_index_map = {}
        combined_questions = []
        combined_code_exercises = []

        for batch_text in batches:
            try:
                chunk_data = cls.generate_quiz_chunk(batch_text, api_key=api_key)
                chunk_cats = chunk_data.get("categories", [])
                
                # Map old cat index to merged cat index
                local_to_global_cat = {}
                for local_idx, cat_name in enumerate(chunk_cats):
                    if cat_name not in cat_index_map:
                        new_idx = len(combined_categories)
                        cat_index_map[cat_name] = new_idx
                        combined_categories.append(cat_name)
                    local_to_global_cat[local_idx] = cat_index_map[cat_name]

                # Adjust questions
                for q in chunk_data.get("questions", []):
                    old_cat = q.get("cat", 0)
                    q["cat"] = local_to_global_cat.get(old_cat, 0)
                    combined_questions.append(q)

                # Append code exercises
                combined_code_exercises.extend(chunk_data.get("code_exercises", []))
            except Exception as e:
                # Log and continue next batch
                print(f"Warning: Failed batch parsing: {e}")
                continue

        # Re-index questions
        for idx, q in enumerate(combined_questions):
            q["id"] = f"Q{idx + 1}"

        if not combined_categories:
            combined_categories = ["Tổng hợp kiến thức"]

        return {
            "categories": combined_categories,
            "questions": combined_questions,
            "code_exercises": combined_code_exercises
        }

    @staticmethod
    def _standardize_data(data: Dict[str, Any]) -> Dict[str, Any]:
        """Ensure IDs, types, and categories are properly formatted."""
        categories = data.get("categories", ["Kiến thức chung"])
        questions = data.get("questions", [])
        code_exercises = data.get("code_exercises", [])

        for idx, q in enumerate(questions):
            q["id"] = f"Q{idx + 1}"
            if "cat" not in q or not isinstance(q["cat"], int) or q["cat"] >= len(categories):
                q["cat"] = 0
            if "opts" not in q or not isinstance(q["opts"], list):
                q["opts"] = ["A", "B", "C", "D"]
            if "ans" not in q or not isinstance(q["ans"], int):
                q["ans"] = 0
            if "ex" not in q:
                q["ex"] = "Đáp án đúng theo nội dung tài liệu."

        return {
            "categories": categories,
            "questions": questions,
            "code_exercises": code_exercises
        }
