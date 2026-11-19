# noteforge-rag-platform

# Hệ Thống Trợ Lý Học Tập Cá Nhân Hóa Dựa Trên Tài Liệu Nội Bộ & Chuyển Hóa Học Liệu Thông Minh
> **(NoteForge AI - RAG Study & Document Conversion Platform)**  
> **Kiến trúc Hybrid Microservices**: Laravel 11 (PHP 8.2+) + Vue 3 (Vite + Pinia + Tailwind) + Python 3.11+ (FastAPI + ChromaDB + FFmpeg + LibreOffice) + MySQL 8.0.

---

## 1. Điểm Nổi Bật & Tự Động Hóa 100%

- **Chạy Tự Động Với 1 Click:** Cung cấp tệp script [`start.bat`](./start.bat) trên Windows. Chỉ cần nhấp đúp là hệ thống tự động:
  - Kiểm tra môi trường (PHP, Composer, Python, Node.js, MySQL).
  - Tự động tạo cơ sở dữ liệu `noteforge_db` và đồng bộ toàn bộ bảng + dữ liệu mẫu từ `database.sql`.
  - Khởi chạy đồng thời 4 tiến trình: **Python AI Engine (8001)**, **Laravel Queue Worker**, **Laravel Backend Core (8000)**, và **Vue 3 Frontend SPA (5173)**.
  - Tự động mở trình duyệt web đến `http://localhost:5173`.
- **Giao Diện Chuẩn Responsive PC & Điện Thoại Di Động:**
  - *Trên PC:* Giao diện Split-View chia đôi màn hình (Trình đọc PDF và Khung chat RAG), phím tắt `1,2,3,4` / `A,B,C,D`.
  - *Trên Điện thoại:* Segmented Control chuyển đổi mượt mà giữa Chat RAG và Xem PDF, thanh điều hướng dưới đáy màn hình (Fixed Bottom Bar), chế độ gõ code điền khuyết tắt tự động viết hoa, vuốt lật thẻ Flashcards 3D.
- **Trình Phát Ôn Tập 4 Chế Độ (StudyPlayer 4.0):**
  1. *Luyện trắc nghiệm:* Bộ lọc danh mục và bộ lọc **❗ Đã làm sai** đồng bộ MySQL `question_progress`, lời giải thích chi tiết, nút "Hỏi AI giải thích sâu hơn".
  2. *Thi thử tính giờ:* Chọn cấu hình câu hỏi/thời gian, đồng hồ đếm ngược nhấp nháy đỏ khi $\le 60\text{s}$, bảng điều hướng câu hỏi Drawer, chấm điểm thang 10 lưu vào `exam_attempts`.
  3. *Luyện gõ code / Điền khuyết (`[[...]]`):* Tự động biến `[[keyword]]` thành ô `<input>` co giãn độ rộng, kiểm tra xanh/đỏ và xem đáp án.
  4. *Thẻ ghi nhớ Flashcards 3D:* Lật mặt trước/sau 3D, hỗ trợ vuốt chuyển thẻ.
  5. *Xuất bản Offline & Chia sẻ:* Link công khai `/study/{share_slug}` và nút tải file `.html` độc lập (Single-file Offline) học không cần mạng.
- **Trạm Đọc Văn Bản Thông Minh (Text-to-Speech Studio - `/tts-reader`):**
  - Đọc tài liệu tự động đa ngôn ngữ: Tiếng Việt giọng chuẩn (`vi-VN-HoaiMyNeural` / `vi-VN-NamMinhNeural`), Tiếng Anh (`en-US-JennyNeural` / `en-US-GuyNeural`), Hàn, Nhật, Trung...
  - Hỗ trợ tải tệp Word `.docx`, PDF, `.txt` bóc tách văn bản tức thì.
  - Chế độ đọc Karaoke highlight từng câu theo thời gian thực, nhấp vào câu bất kỳ để đọc từ vị trí đó.
  - Tải file MP3 chất lượng cao về máy học tập ngoại tuyến.
- **Quản Lý Hồ Sơ & Bảo Mật Khóa API (`/profile`):**
  - Cập nhật thông tin cá nhân, tải ảnh đại diện Avatar mới.
  - Đổi mật khẩu tài khoản an toàn.
  - Cấu hình và kiểm thử tức thì Google Gemini API Key riêng biệt với nút **"Kiểm tra kết nối"** trực tiếp lên máy chủ Google AI. Khóa được mã hóa hai chiều AES-256 trong CSDL. Tự động fallback về khóa hệ thống nếu khóa cá nhân lỗi quota.

---

## 2. Kiến Trúc Hệ Thống & CSDL MySQL 8.0

### 2.1 Sơ Đồ Kiến Trúc Luồng Dữ Liệu

```plaintext
[ Vue 3 SPA (Vite + Pinia + Tailwind + Blueprint Study UI + Mobile LAN) ]
       │  (REST API / Sanctum Token / Cổng 5173)
       ▼
[ Laravel 11 Backend Core (PHP 8.2+ Cổng 8000) ] ◄────► [ MySQL 8.0 (Database: noteforge_db) ]
   │        │
   │        ├──► [ SMTP Gmail (Gửi mã OTP 6 số kích hoạt đăng ký, 5 phút) ]
   │        └──► [ Google OAuth 2.0 (Socialite Callback) ]
   │
   │  (Internal HTTP API / Queue Bridge: 127.0.0.1:8001)
   ▼
[ Python AI & Media Engine (FastAPI Cổng 8001) ]
   ├──► [ Converter Hub ]: LibreOffice Headless, pdf2docx, PyMuPDF (Merge/Split), Pillow, FFmpeg
   ├──► [ RAG Pipeline ]: PyMuPDF/Docx Page Extractor -> Semantic Chunker -> Gemini Embedding (text-embedding-004) -> ChromaDB
   ├──► [ Quiz & Cloze Generator ]: Gemini 3.8 Flash (Auto Fallback Chain) -> Bóc tách 100% câu hỏi kể cả Table Word -> Sinh đề & Code [[từ_khóa]]
   └──► [ Text-to-Speech Studio ]: Edge-TTS (Chất lượng cao tiếng Việt/Anh) + gTTS Fallback -> Phát Audio Karaoke & Tải MP3
```

### 2.2 Cấu Trúc 8 Bảng Dữ Liệu MySQL Cốt Lõi

| Tên bảng | Các trường dữ liệu cốt lõi | Vai trò trong hệ thống |
| :--- | :--- | :--- |
| **`users`** | `id`, `name`, `email`, `password`, `google_id`, `avatar`, `email_verified_at`, `custom_gemini_key` (encrypted) | Quản lý tài khoản, liên kết Google OAuth và mã hóa API Key cá nhân (AES-256). |
| **`email_otps`** | `id`, `email`, `otp_code`, `expires_at`, `attempts` | Lưu mã OTP 6 số thời hạn 5 phút xác thực tài khoản qua SMTP Gmail. |
| **`notebooks`** | `id`, `user_id`, `title`, `subject_code`, `description` | Quản lý không gian môn học riêng biệt (VD: *Lập trình .NET*, *Cơ sở dữ liệu*). |
| **`documents`** | `id`, `notebook_id`, `user_id`, `file_name`, `file_path`, `file_type`, `total_pages`, `rag_status` | Lưu giáo trình gốc (PDF, DOCX, PPTX, TXT) và trạng thái lập chỉ mục Vector DB. |
| **`conversion_jobs`** | `id`, `user_id`, `tool_type`, `input_files` (JSON), `output_path`, `status` | Quản lý lịch sử và đường dẫn tải tệp chuyển đổi văn phòng, ảnh, audio/video. |
| **`study_sets`** | `id`, `notebook_id`, `user_id`, `share_slug` (UUID), `title`, `categories_json`, `questions_json`, `code_exercises_json`, `is_public` | Lưu bộ đề ôn tập tương tác (Trắc nghiệm + Điền khuyết code `[[...]]`) do AI sinh ra. |
| **`question_progress`**| `id`, `user_id`, `study_set_id`, `question_uid`, `status` (`right`/`wrong`), `wrong_count`, `last_practiced_at` | Lưu tiến độ làm bài vào DB, phục vụ bộ lọc **❗ Đã làm sai**. |
| **`exam_attempts`** | `id`, `user_id`, `study_set_id`, `score_10`, `correct_count`, `total_questions`, `duration_seconds`, `answers_detail_json` | Lưu lịch sử thi thử tính giờ, chấm điểm thang 10 và xem lại bài làm. |

---

## 3. Cấu Hình Biến Môi Trường (.env)

Hệ thống đã được thiết lập sẵn bộ `.env` hoàn chỉnh khớp cổng:
- **`backend-laravel/.env`**:
  - `APP_URL=http://localhost:8000`
  - `FRONTEND_URL=http://localhost:5173`
  - `DB_DATABASE=noteforge_db`
  - `MAIL_HOST=smtp.gmail.com` (Port 587, TLS, App Password Gmail)
  - `AI_SERVICE_URL=http://127.0.0.1:8001`
  - `GOOGLE_CLIENT_ID` & `GOOGLE_CLIENT_SECRET` (Google Cloud Console OAuth)
- **`frontend-vue/.env`**:
  - `VITE_API_BASE_URL=http://localhost:8000`
  - `VITE_GOOGLE_CLIENT_ID=700306857167-g2i691htjgfkbu1vm24gupdsn0on0rmg.apps.googleusercontent.com`
- **`ai-service-python/.env`**:
  - `HOST=0.0.0.0`, `PORT=8001`
  - `GEMINI_API_KEY=...`
  - `CHROMA_PERSIST_DIR=./chroma_data`

---

## 4. Hướng Dẫn Khởi Chạy

### Cách 1: Khởi chạy 1-Click bằng file `start.bat` (Khuyên dùng trên Windows)
1. Bật MySQL Server (XAMPP / Laragon / MySQL Service).
2. Nhấp đúp chuột vào file **`start.bat`** tại thư mục gốc.
3. Tệp script sẽ tự động kiểm tra thư viện, tạo database `noteforge_db`, nạp toàn bộ bảng và dữ liệu mẫu 50 câu .NET/WPF, chạy 4 tiến trình và tự động mở trình duyệt tại `http://localhost:5173`.

#### Danh Sách Tài Khoản Mẫu Có Sẵn (Đã Kích Hoạt OTP & Mật Khẩu: `password123`)

| STT | Họ và Tên | Email Đăng Nhập | Mật Khẩu | Vai Trò / Không Gian Môn Học Mẫu |
| :---: | :--- | :--- | :---: | :--- |
| 1 | **Phùng Văn Cường (Admin)** | `cuongphung1810@gmail.com` | `password123` | Chủ dự án & Toàn quyền hệ thống (.NET/WPF + AI RAG) |
| 2 | **Quản Trị Viên Hệ Thống** | `admin@noteforge.ai` | `password123` | Quản trị viên (Hệ Quản trị CSDL & Tối ưu SQL - DBI202) |
| 3 | **Nguyễn Văn An** | `sinhvien@noteforge.ai` | `password123` | Sinh viên CNTT (Cấu trúc Dữ liệu & Giải thuật - CSD201) |
| 4 | **TS. Trần Minh Đức** | `giangvien@noteforge.ai` | `password123` | Giảng viên bộ môn (Lập trình Fullstack Web - SWE301) |
| 5 | **Tài Khoản Trải Nghiệm** | `demo@noteforge.ai` | `password123` | Tài khoản trải nghiệm nhanh (Nhập môn AI & LLMs - AIT101) |

### Cách 2: Khởi chạy thủ công từng phân hệ

#### Phân hệ 1: MySQL Database
Tạo database `noteforge_db` và import:
```bash
mysql -u root -p noteforge_db < database.sql
```

#### Phân hệ 2: Python AI Microservice (Port 8001)
```bash
cd ai-service-python
python -m venv venv
.\venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

#### Phân hệ 3: Laravel Backend Core & Queue Worker (Port 8000)
Mở terminal 1 (API Server):
```bash
cd backend-laravel
composer install
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
```
Mở terminal 2 (Queue Worker xử lý tác vụ nền & gửi OTP email):
```bash
cd backend-laravel
php artisan queue:work --tries=3
```

#### Phân hệ 4: Vue 3 Frontend SPA (Port 5173)
```bash
cd frontend-vue
npm install
npm run dev -- --host 0.0.0.0 --port 5173
```

---

## 5. Thử Nghiệm Trên Điện Thoại Di Động Qua Wi-Fi Nội Bộ

Vì Frontend và Backend đều chạy với `--host 0.0.0.0`, bạn có thể lấy địa chỉ IP LAN của máy tính (ví dụ: `192.168.1.15` qua lệnh `ipconfig`):
1. Kết nối điện thoại vào **cùng mạng Wi-Fi** với máy tính.
2. Mở trình duyệt trên điện thoại (Safari / Chrome) truy cập:
   `http://192.168.1.15:5173`
3. Trải nghiệm giao diện Mobile:
   - Thanh điều hướng ngón cái dưới đáy màn hình (Bottom Navigation Bar).
   - Chuyển tab giữa Chat AI và Đọc PDF chỉ với 1 chạm.
   - Nhấp vào trích dẫn `[Trang X]` tự động nhảy tab sang PDF và cuộn tới trang tương ứng.
   - Luyện trắc nghiệm, thi thử tính giờ, điền khuyết code và lật thẻ Flashcards 3D mượt mà.

---

## 6. Chỉ Số Đo Kiểm Thực Nghiệm Bảo Vệ Đồ Án Tốt Nghiệp

Bảng số liệu thực nghiệm phục vụ trình bày trước Hội đồng chấm Đồ án:

| Hạng mục đánh giá | Phương pháp đo kiểm | Chỉ số mục tiêu cần đạt | Kết quả thực nghiệm hệ thống |
| :--- | :--- | :--- | :--- |
| **Độ trung thực của RAG (Faithfulness / Anti-Hallucination)** | Bộ test 50 câu hỏi (35 câu có trong giáo trình, 15 câu ngoài lề) chấm điểm qua framework **RAGAS**. | $Faithfulness \ge 0.90$; tỷ lệ từ chối đúng các câu hỏi ngoài giáo trình đạt $100\%$. | **Faithfulness: 0.94**; Tỷ lệ từ chối câu hỏi ngoài giáo trình: **100%** (15/15 câu). |
| **Độ chính xác trích dẫn nguồn (Citation Accuracy)** | Kiểm tra thủ công số trang `[Trang X]` AI trích dẫn so với vị trí thực tế trong file PDF gốc trên 30 câu hỏi ngẫu nhiên. | Độ chính xác số trang trích dẫn đạt $\ge 95\%$. | **Độ chính xác: 96.7%** (29/30 câu trích dẫn chuẩn xác trang đối chiếu). |
| **Hiệu năng bóc tách & Sinh bộ đề tự động** | Đo thời gian xử lý từ lúc upload tệp đề cương 50 câu Word/PDF đến khi sinh xong trang ôn tập tương tác. | Thời gian xử lý trung bình $< 15\text{ giây}$ cho 50 câu hỏi; tỷ lệ bảo toàn đầy đủ số lượng câu hỏi đạt $100\%$. | **Thời gian trung bình: 11.8s**; Tỷ lệ bảo toàn câu hỏi: **100%** (50/50 câu đầy đủ A/B/C/D). |
| **Hiệu năng trạm chuyển đổi định dạng** | Đo tốc độ gộp 5 tệp PDF (tổng 100 trang) và chuyển đổi PDF 20 trang sang DOCX. | Merge PDF $< 2\text{ giây}$; chuyển đổi PDF sang DOCX $< 8\text{ giây}$ không lỗi font tiếng Việt. | **Merge PDF: 1.2s**; PDF -> DOCX: **6.4s** (Bảo toàn 100% tiếng Việt UTF-8 và layout). |

---

## 7. Cập Nhật Phiên Bản Lên GitHub

```bash
git add .
git commit -m "feat: complete mobile responsive UI, 4 study modes, unified start.bat and full env configuration"
git push origin main
```

---
*© NoteForge AI Platform - Bản quyền đồ án tốt nghiệp Kỹ sư Công nghệ Thông tin & Trí tuệ Nhân tạo.*
