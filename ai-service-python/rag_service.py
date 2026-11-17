import os
import uuid
from typing import List, Dict, Any, Optional, Tuple
from pathlib import Path

import fitz  # PyMuPDF
from docx import Document as DocxDocument
from pptx import Presentation
from langchain_text_splitters import RecursiveCharacterTextSplitter
import chromadb
from chromadb.config import Settings
import google.generativeai as genai

import config

GROUNDED_SYSTEM_PROMPT = """Bạn là Trợ lý Học tập Học thuật chuyên sâu của hệ thống RAG Study Platform. Nhiệm vụ của bạn là giải đáp câu hỏi của sinh viên DỰA TRÊN DUY NHẤT các đoạn trích tài liệu nội bộ được cung cấp trong phần [NGỮ CẢNH TÀI LIỆU].

QUY TẮC BẮT BUỘC (VI PHẠM SẼ BỊ TỪ CHỐI):
1. KHÔNG sử dụng kiến thức bên ngoài, KHÔNG suy diễn, KHÔNG tự bịa đặt thông tin không có trong [NGỮ CẢNH TÀI LIỆU].
2. Nếu [NGỮ CẢNH TÀI LIỆU] trống hoặc không chứa thông tin đủ để trả lời câu hỏi, hãy trả lời chính xác câu sau:
   "⚠️ Tài liệu môn học bạn đã tải lên hiện không đề cập đến nội dung này. Vui lòng bổ sung thêm giáo trình hoặc kiểm tra lại từ khóa."
3. Mọi luận điểm quan trọng trong câu trả lời BẮT BUỘC phải kèm trích dẫn nguồn ngay cuối câu theo đúng định dạng: [Tên_File - Trang X].
4. Trình bày câu trả lời bằng tiếng Việt rõ ràng, mạch lạc, sử dụng gạch đầu dòng hoặc bảng so sánh khi cần thiết để sinh viên dễ ôn thi.

[NGỮ CẢNH TÀI LIỆU]:
{retrieved_chunks_with_metadata}

[CÂU HỎI CỦA SINH VIÊN]:
{user_question}"""

REJECTION_MESSAGE = "⚠️ Tài liệu môn học bạn đã tải lên hiện không đề cập đến nội dung này. Vui lòng bổ sung thêm giáo trình hoặc kiểm tra lại từ khóa."

class RagService:
    def __init__(self):
        self.chroma_client = chromadb.PersistentClient(
            path=config.CHROMA_PERSIST_DIR,
            settings=Settings(anonymized_telemetry=False)
        )
        self.collection = self.chroma_client.get_or_create_collection(
            name="noteforge_rag",
            metadata={"hnsw:space": "cosine"}
        )
        self.text_splitter = RecursiveCharacterTextSplitter(
            chunk_size=config.CHUNK_SIZE,
            chunk_overlap=config.CHUNK_OVERLAP,
            separators=["\n\n", "\n", ". ", " ", ""]
        )

    def extract_text_with_metadata(
        self,
        file_path: str,
        file_name: str,
        notebook_id: int,
        document_id: int,
        user_id: int
    ) -> List[Dict[str, Any]]:
        """Extract text from PDF, DOCX, PPTX, or TXT preserving page-level metadata."""
        ext = Path(file_path).suffix.lower()
        pages_data = []

        if ext == ".pdf":
            doc = fitz.open(file_path)
            for page_idx in range(len(doc)):
                page = doc[page_idx]
                text = page.get_text("text").strip()
                if text:
                    pages_data.append({
                        "text": text,
                        "metadata": {
                            "notebook_id": str(notebook_id),
                            "document_id": str(document_id),
                            "user_id": str(user_id),
                            "file_name": file_name,
                            "page_number": page_idx + 1
                        }
                    })
            doc.close()

        elif ext in [".docx", ".doc"]:
            doc = DocxDocument(file_path)
            current_page = 1
            char_count = 0
            page_text_buffer = []
            
            for para in doc.paragraphs:
                p_text = para.text.strip()
                if not p_text:
                    continue
                page_text_buffer.append(p_text)
                char_count += len(p_text)
                # Approximate 1800 chars per page in DOCX
                if char_count > 1800:
                    pages_data.append({
                        "text": "\n".join(page_text_buffer),
                        "metadata": {
                            "notebook_id": str(notebook_id),
                            "document_id": str(document_id),
                            "user_id": str(user_id),
                            "file_name": file_name,
                            "page_number": current_page
                        }
                    })
                    current_page += 1
                    char_count = 0
                    page_text_buffer = []

            if page_text_buffer:
                pages_data.append({
                    "text": "\n".join(page_text_buffer),
                    "metadata": {
                        "notebook_id": str(notebook_id),
                        "document_id": str(document_id),
                        "user_id": str(user_id),
                        "file_name": file_name,
                        "page_number": current_page
                    }
                })

        elif ext in [".pptx", ".ppt"]:
            prs = Presentation(file_path)
            for slide_idx, slide in enumerate(prs.slides):
                slide_texts = []
                for shape in slide.shapes:
                    if hasattr(shape, "text") and shape.text.strip():
                        slide_texts.append(shape.text.strip())
                if slide_texts:
                    pages_data.append({
                        "text": "\n".join(slide_texts),
                        "metadata": {
                            "notebook_id": str(notebook_id),
                            "document_id": str(document_id),
                            "user_id": str(user_id),
                            "file_name": file_name,
                            "page_number": slide_idx + 1
                        }
                    })

        elif ext in [".txt", ".md"]:
            with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
                content = f.read()
            # Split into virtual pages of ~2000 chars
            chunks = [content[i:i + 2000] for i in range(0, len(content), 2000)]
            for idx, c in enumerate(chunks):
                if c.strip():
                    pages_data.append({
                        "text": c.strip(),
                        "metadata": {
                            "notebook_id": str(notebook_id),
                            "document_id": str(document_id),
                            "user_id": str(user_id),
                            "file_name": file_name,
                            "page_number": idx + 1
                        }
                    })

        return pages_data

    def get_embedding(self, text: str, api_key: Optional[str] = None) -> List[float]:
        """Generate vector embedding using Gemini models/text-embedding-004."""
        active_key = api_key or config.GEMINI_API_KEY
        if not active_key:
            raise ValueError("Gemini API key is required for embedding generation.")
            
        genai.configure(api_key=active_key)
        response = genai.embed_content(
            model=config.EMBEDDING_MODEL,
            content=text,
            task_type="retrieval_document"
        )
        return response['embedding']

    def get_embeddings_batch(self, texts: List[str], api_key: Optional[str] = None) -> List[List[float]]:
        """Batch generate vector embeddings."""
        active_key = api_key or config.GEMINI_API_KEY
        if not active_key:
            raise ValueError("Gemini API key is required for embedding generation.")
            
        genai.configure(api_key=active_key)
        embeddings = []
        batch_size = 20
        for i in range(0, len(texts), batch_size):
            batch = texts[i:i + batch_size]
            response = genai.embed_content(
                model=config.EMBEDDING_MODEL,
                content=batch,
                task_type="retrieval_document"
            )
            if "embedding" in response:
                embeddings.extend(response["embedding"])
        return embeddings

    def ingest_document(
        self,
        file_path: str,
        file_name: str,
        notebook_id: int,
        document_id: int,
        user_id: int,
        api_key: Optional[str] = None
    ) -> Dict[str, Any]:
        """Process document into chunks, calculate embeddings, and persist in ChromaDB."""
        pages = self.extract_text_with_metadata(
            file_path=file_path,
            file_name=file_name,
            notebook_id=notebook_id,
            document_id=document_id,
            user_id=user_id
        )

        if not pages:
            return {"status": "empty", "total_chunks": 0, "total_pages": 0}

        all_chunks = []
        all_metadatas = []
        all_ids = []

        for p in pages:
            splits = self.text_splitter.split_text(p["text"])
            for split_idx, split_text in enumerate(splits):
                all_chunks.append(split_text)
                meta = dict(p["metadata"])
                meta["split_idx"] = split_idx
                all_metadatas.append(meta)
                all_ids.append(f"doc_{document_id}_p{meta['page_number']}_s{split_idx}_{uuid.uuid4().hex[:6]}")

        # Compute embeddings in batches
        embeddings = self.get_embeddings_batch(all_chunks, api_key=api_key)

        # Upsert into ChromaDB
        self.collection.upsert(
            ids=all_ids,
            embeddings=embeddings,
            documents=all_chunks,
            metadatas=all_metadatas
        )

        return {
            "status": "indexed",
            "document_id": document_id,
            "total_chunks": len(all_chunks),
            "total_pages": len(pages)
        }

    def delete_document(self, document_id: int):
        """Remove all chunks associated with a document_id."""
        self.collection.delete(
            where={"document_id": str(document_id)}
        )

    def query_rag(
        self,
        question: str,
        notebook_id: int,
        api_key: Optional[str] = None,
        top_k: int = config.TOP_K,
        similarity_threshold: float = config.SIMILARITY_THRESHOLD
    ) -> Dict[str, Any]:
        """
        Query Vector DB with strict threshold filtering, strict grounding,
        and citation formatting.
        """
        active_key = api_key or config.GEMINI_API_KEY
        if not active_key:
            raise ValueError("Gemini API key is required for RAG query.")

        genai.configure(api_key=active_key)

        # 1. Embed user question
        q_emb_resp = genai.embed_content(
            model=config.EMBEDDING_MODEL,
            content=question,
            task_type="retrieval_query"
        )
        query_embedding = q_emb_resp['embedding']

        # 2. Query ChromaDB with notebook_id filter
        results = self.collection.query(
            query_embeddings=[query_embedding],
            n_results=top_k,
            where={"notebook_id": str(notebook_id)}
        )

        retrieved_chunks = []
        citations = []

        if results and results.get("documents") and len(results["documents"]) > 0:
            docs = results["documents"][0]
            metas = results["metadatas"][0] if results.get("metadatas") else []
            distances = results["distances"][0] if results.get("distances") else []

            for doc_text, meta, dist in zip(docs, metas, distances):
                # Cosine distance: similarity = 1.0 - dist
                similarity = max(0.0, 1.0 - float(dist))
                if similarity >= similarity_threshold:
                    f_name = meta.get("file_name", "Tài liệu")
                    p_num = meta.get("page_number", 1)
                    
                    retrieved_chunks.append(
                        f"--- [NGUỒN: {f_name} - Trang {p_num}] (Độ tin cậy: {similarity:.2f}) ---\n{doc_text}\n"
                    )
                    citations.append({
                        "file_name": f_name,
                        "page_number": p_num,
                        "similarity": round(similarity, 4),
                        "preview": doc_text[:200] + "..." if len(doc_text) > 200 else doc_text
                    })

        # 3. Strict Grounding Check: If no chunks pass threshold, reject immediately
        if not retrieved_chunks:
            return {
                "answer": REJECTION_MESSAGE,
                "is_grounded": False,
                "citations": [],
                "chunks_retrieved": 0
            }

        # 4. Synthesize answer with Gemini 2.5 Flash
        context_block = "\n".join(retrieved_chunks)
        prompt = GROUNDED_SYSTEM_PROMPT.format(
            retrieved_chunks_with_metadata=context_block,
            user_question=question
        )

        model = genai.GenerativeModel(config.CHAT_MODEL)
        response = model.generate_content(prompt)

        return {
            "answer": response.text.strip(),
            "is_grounded": True,
            "citations": citations,
            "chunks_retrieved": len(retrieved_chunks)
        }
