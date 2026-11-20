import json
import re
import time
import logging
from typing import Dict, Any, List, Optional
from pathlib import Path

import fitz  # PyMuPDF
from docx import Document as DocxDocument
import requests
from tenacity import retry, stop_after_attempt, wait_exponential, retry_if_exception_type

try:
    from . import config
    from .ai_client import generate_with_fallback, clean_json_string
except ImportError:
    import config
    from ai_client import generate_with_fallback, clean_json_string

logger = logging.getLogger(__name__)

QUIZ_GENERATOR_PROMPT = """Bạn là chuyên gia khảo thí và biên soạn học liệu tương tác đại học. Hãy phân tích đoạn nội dung tài liệu được cung cấp dưới đây và trích xuất TOÀN BỘ kiến thức, câu hỏi, bài tập thành một chuỗi JSON chuẩn xác gồm 4 thành phần:

1. "questions": Danh sách câu hỏi trắc nghiệm (Tất cả câu trắc nghiệm A, B, C, D có trong tài liệu, hoặc tự sinh nếu là tài liệu lý thuyết).
   - "id": Định danh tạm thời ("Q1", "Q2"...)
   - "cat": Chỉ số chủ đề (0, 1, 2...) tương ứng với mảng "categories"
   - "en": Nội dung câu hỏi đầy đủ
   - "opts": Mảng đúng 4 lựa chọn [A, B, C, D]
   - "ans": Chỉ số lựa chọn đúng (0 cho A, 1 cho B, 2 cho C, 3 cho D)
   - "ex": Lời giải thích chi tiết tại sao đáp án này đúng

2. "code_exercises": Các bài tập lập trình / mã nguồn / câu lệnh quan trọng được đục lỗ điền khuyết:
   - "title": Tên bài tập / chức năng (VD: "Triển khai INotifyPropertyChanged trong C#")
   - "code": Đoạn mã nguồn có đặt cặp ngoặc vuông kép [[từ_khóa]] quanh từ khóa cốt lõi để sinh viên tự gõ điền khuyết (từ 2 đến 6 ô trống mỗi bài).

3. "shorts": Các câu hỏi ngắn lý thuyết / câu hỏi trả lời nhanh (nếu trong tài liệu có "Câu hỏi ngắn", "Part 2", hoặc các định nghĩa ngắn gọn):
   - "id": Định danh ("S1", "S2"...)
   - "q": Nội dung câu hỏi ngắn
   - "a": Câu trả lời chuẩn xác, súc tích

4. "essays": Các bài tập tự luận lớn / câu hỏi mở rộng / bài tập thực hành toàn diện (nếu tài liệu có "Tự luận", "Part 3", "Bài tập lớn" hoặc các ca nghiên cứu):
   - "id": Định danh ("E1", "E2"...)
   - "title": Tên chủ đề / bài tự luận
   - "content": Nội dung dàn ý chi tiết, mã nguồn đầy đủ hoặc bảng phân tích so sánh

CẤU TRÚC JSON BẮT BUỘC (Chỉ trả về JSON thuần túy trong cặp ```json ... ```):
{{
  "categories": ["Chủ đề 1", "Chủ đề 2"],
  "questions": [
    {{
      "id": "Q1",
      "cat": 0,
      "en": "Nội dung câu hỏi?",
      "opts": ["Đáp án A", "Đáp án B", "Đáp án C", "Đáp án D"],
      "ans": 0,
      "ex": "Giải thích chi tiết."
    }}
  ],
  "code_exercises": [
    {{
      "title": "Tiêu đề bài code",
      "code": "public class Base : [[INotifyPropertyChanged]] \\n{{\\n}}"
    }}
  ],
  "shorts": [
    {{
      "id": "S1",
      "q": "Câu hỏi ngắn lý thuyết?",
      "a": "Câu trả lời ngắn gọn, chính xác."
    }}
  ],
  "essays": [
    {{
      "id": "E1",
      "title": "Tên bài tự luận / đề bài",
      "content": "Lời giải chi tiết hoặc dàn ý đáp án mẫu."
    }}
  ]
}}

[NỘI DUNG TÀI LIỆU CẦN BÓC TÁCH]:
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
                        unique_vals = []
                        for v in row_vals:
                            if not unique_vals or unique_vals[-1] != v:
                                unique_vals.append(v)
                        if unique_vals:
                            parts.append(" | ".join(unique_vals))
                return "\n\n".join(parts)
            except Exception as e:
                logger.error(f"Error parsing docx with python-docx: {e}")
                with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                    return f.read()

        elif ext in [".txt", ".md"]:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                return f.read()

        raise ValueError(f"Định dạng tệp không được hỗ trợ: {ext}")

    @classmethod
    def generate_quiz_chunk_with_retry(
        cls,
        text_chunk: str,
        api_key: Optional[str] = None
    ) -> Dict[str, Any]:
        """Generate quiz JSON for a single text chunk with exponential backoff retry on 429/503."""
        prompt = QUIZ_GENERATOR_PROMPT.format(document_chunk_text=text_chunk)
        generation_config = {
            "response_mime_type": "application/json",
            "temperature": 0.2,
        }

        max_attempts = 3
        last_exc = None
        for attempt in range(max_attempts):
            try:
                raw_response = generate_with_fallback(
                    api_key=api_key,
                    prompt=prompt,
                    generation_config=generation_config
                )
                return clean_json_string(raw_response)
            except Exception as e:
                last_exc = e
                err_str = str(e).lower()
                if "429" in err_str or "quota" in err_str or "resourceexhausted" in err_str or "503" in err_str:
                    wait_sec = (attempt + 1) * 8
                    logger.warning(f"Gemini API rate limited (429/503). Retrying in {wait_sec}s (Attempt {attempt+1}/{max_attempts})...")
                    time.sleep(wait_sec)
                else:
                    logger.warning(f"Chunk generation attempt {attempt+1} failed: {e}")
                    time.sleep(3)

        raise last_exc or RuntimeError("Failed to generate quiz chunk after retries.")

    @classmethod
    def generate_full_study_set(
        cls,
        file_path: Optional[str] = None,
        raw_text: Optional[str] = None,
        api_key: Optional[str] = None,
        study_set_id: Optional[int] = None,
        callback_url: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Processes document or text:
        - Smart chunking: Max 11,000 characters per batch.
        - Delays 15s between batches to strictly prevent 429 quota errors (15 RPM).
        - Reports live progress to Laravel webhook.
        - Aggregates questions, code_exercises, shorts, and essays.
        """
        target_callback = callback_url
        if not target_callback and study_set_id:
            internal_base = getattr(config, "LARAVEL_INTERNAL_URL", "http://127.0.0.1:8000").rstrip("/")
            target_callback = f"{internal_base}/api/internal/study-sets/{study_set_id}/progress"

        def report_progress(percent: int, message: str):
            if target_callback:
                try:
                    requests.post(
                        target_callback,
                        json={
                            "progress": int(percent),
                            "status_message": str(message),
                            "status": "processing"
                        },
                        timeout=5.0
                    )
                except Exception as cb_err:
                    logger.warning(f"Failed to post progress to {target_callback}: {cb_err}")

        report_progress(10, "Đang trích xuất nội dung và phân tích cấu trúc tài liệu...")

        extracted_text = ""
        if file_path:
            extracted_text = cls.extract_full_text(file_path)

        if raw_text and raw_text.strip():
            if extracted_text:
                extracted_text = extracted_text + "\n\n=== NỘI DUNG BỔ SUNG ===\n\n" + raw_text.strip()
            else:
                extracted_text = raw_text.strip()

        if not extracted_text.strip():
            raise ValueError("Không tìm thấy nội dung văn bản nào để trích xuất trong tệp hoặc ghi chú.")

        text = extracted_text.strip()
        total_chars = len(text)
        logger.info(f"Total extracted document length: {total_chars} characters.")

        # Smart Batching: Guarantee each chunk is <= 11,000 characters
        MAX_CHUNK_CHARS = 11000
        paragraphs = text.split("\n\n")
        batches: List[str] = []
        current_batch: List[str] = []
        current_len = 0

        for p in paragraphs:
            p_clean = p.strip()
            if not p_clean:
                continue

            if len(p_clean) > MAX_CHUNK_CHARS:
                lines = p_clean.split("\n")
                for line in lines:
                    line_len = len(line)
                    if current_len + line_len > MAX_CHUNK_CHARS and current_batch:
                        batches.append("\n\n".join(current_batch))
                        current_batch = [line]
                        current_len = line_len
                    else:
                        current_batch.append(line)
                        current_len += line_len
            else:
                p_len = len(p_clean)
                if current_len + p_len > MAX_CHUNK_CHARS and current_batch:
                    batches.append("\n\n".join(current_batch))
                    current_batch = [p_clean]
                    current_len = p_len
                else:
                    current_batch.append(p_clean)
                    current_len += p_len

        if current_batch:
            batches.append("\n\n".join(current_batch))

        total_batches = len(batches)
        logger.info(f"Divided document into {total_batches} batch(es) for AI processing...")
        report_progress(18, f"Tài liệu đã chia thành {total_batches} phần xử lý chuyên sâu...")

        combined_categories: List[str] = []
        cat_index_map: Dict[str, int] = {}
        combined_questions: List[Dict[str, Any]] = []
        combined_code_exercises: List[Dict[str, Any]] = []
        combined_shorts: List[Dict[str, Any]] = []
        combined_essays: List[Dict[str, Any]] = []

        seen_question_keys = set()
        seen_short_keys = set()
        last_batch_error = None

        for b_idx, batch_text in enumerate(batches):
            current_pct = int(20 + (b_idx / total_batches) * 60)
            report_progress(
                current_pct,
                f"AI đang trích xuất câu hỏi và bài tập (Phần {b_idx + 1}/{total_batches})..."
            )
            logger.info(f"--> Processing batch {b_idx + 1}/{total_batches} ({len(batch_text)} chars)...")

            try:
                chunk_data = cls.generate_quiz_chunk_with_retry(batch_text, api_key=api_key)

                # 1. Merge categories
                chunk_cats = chunk_data.get("categories", [])
                local_to_global_cat = {}
                for local_idx, cat_name in enumerate(chunk_cats):
                    clean_cat = str(cat_name).strip()
                    if clean_cat not in cat_index_map:
                        new_idx = len(combined_categories)
                        cat_index_map[clean_cat] = new_idx
                        combined_categories.append(clean_cat)
                    local_to_global_cat[local_idx] = cat_index_map[clean_cat]

                # 2. Merge and deduplicate questions
                for q in chunk_data.get("questions", []):
                    en_text = str(q.get("en", "")).strip()
                    norm_key = re.sub(r"\s+", " ", en_text.lower())
                    if norm_key and norm_key in seen_question_keys:
                        continue
                    if norm_key:
                        seen_question_keys.add(norm_key)

                    old_cat = q.get("cat", 0)
                    q["cat"] = local_to_global_cat.get(old_cat, 0)
                    combined_questions.append(q)

                # 3. Merge code exercises
                combined_code_exercises.extend(chunk_data.get("code_exercises", []))

                # 4. Merge shorts (Short Answer Questions)
                for s in chunk_data.get("shorts", []):
                    sq = str(s.get("q", "")).strip()
                    s_key = re.sub(r"\s+", " ", sq.lower())
                    if s_key and s_key not in seen_short_keys:
                        seen_short_keys.add(s_key)
                        combined_shorts.append(s)

                # 5. Merge essays (Long Essay Questions)
                combined_essays.extend(chunk_data.get("essays", []))

                done_pct = int(20 + ((b_idx + 1) / total_batches) * 60)
                report_progress(
                    done_pct,
                    f"Đã hoàn thành phần {b_idx + 1}/{total_batches} ({len(combined_questions)} trắc nghiệm, {len(combined_shorts)} câu ngắn)..."
                )

            except Exception as e:
                logger.error(f"Error in batch {b_idx + 1}: {e}")
                last_batch_error = e

            # Rate limit breathing room: delay 15 seconds between multiple batches to strictly prevent 429
            if b_idx < total_batches - 1:
                logger.info("Applying 15-second breathing room delay to prevent Google 429 quota limits...")
                report_progress(
                    int(20 + ((b_idx + 1) / total_batches) * 60),
                    f"Nghỉ nhịp 15s để tối ưu tốc độ & hạn ngạch AI (Phần {b_idx + 2}/{total_batches})..."
                )
                time.sleep(15)

        # Validate that we got at least something
        if not combined_questions and not combined_code_exercises and not combined_shorts and not combined_essays:
            raise ValueError(
                f"Gemini AI không trích xuất được câu hỏi nào từ tài liệu. Lỗi gần nhất: {last_batch_error or 'Dữ liệu rỗng'}"
            )

        report_progress(88, "Đang tổng hợp kết quả và chuẩn hóa định danh câu hỏi...")

        # Re-index questions sequentially
        for idx, q in enumerate(combined_questions):
            q["id"] = f"Q{idx + 1}"

        # Re-index shorts sequentially
        for idx, s in enumerate(combined_shorts):
            s["id"] = f"S{idx + 1}"

        # Re-index essays sequentially
        for idx, e in enumerate(combined_essays):
            e["id"] = f"E{idx + 1}"

        if not combined_categories:
            combined_categories = ["Tổng hợp kiến thức"]

        report_progress(95, "Đang kiểm tra tính toàn vẹn và đồng bộ vào hệ thống...")

        return cls._standardize_data({
            "categories": combined_categories,
            "questions": combined_questions,
            "code_exercises": combined_code_exercises,
            "shorts": combined_shorts,
            "essays": combined_essays,
        })

    @staticmethod
    def _standardize_data(data: Dict[str, Any]) -> Dict[str, Any]:
        """Ensure IDs, types, and categories are properly formatted."""
        categories = data.get("categories", ["Kiến thức chung"])
        questions = data.get("questions", [])
        code_exercises = data.get("code_exercises", [])
        shorts = data.get("shorts", [])
        essays = data.get("essays", [])

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

            ex = q.get("ex", "Đáp án chính xác theo giáo trình.")

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
            title = c.get("title", f"Bài tập điền khuyết {idx + 1}").strip()
            if code_text:
                valid_code_exercises.append({
                    "title": title,
                    "code": code_text
                })

        valid_shorts = []
        for idx, s in enumerate(shorts):
            if not isinstance(s, dict):
                continue
            q_text = s.get("q", "").strip()
            a_text = s.get("a", "").strip()
            if q_text and a_text:
                valid_shorts.append({
                    "id": f"S{len(valid_shorts) + 1}",
                    "q": q_text,
                    "a": a_text
                })

        valid_essays = []
        for idx, e in enumerate(essays):
            if not isinstance(e, dict):
                continue
            e_title = e.get("title", f"Bài tự luận {idx + 1}").strip()
            e_content = e.get("content", "").strip()
            if e_title or e_content:
                valid_essays.append({
                    "id": f"E{len(valid_essays) + 1}",
                    "title": e_title or f"Bài tự luận {idx + 1}",
                    "content": e_content
                })

        return {
            "categories": categories,
            "questions": valid_questions,
            "code_exercises": valid_code_exercises,
            "shorts": valid_shorts,
            "essays": valid_essays,
        }
