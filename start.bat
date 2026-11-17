@echo off
chcp 65001 >nul
title NoteForge AI - Unified One-Click Launcher
color 0B

echo ===============================================================================
echo                ⚡ NOTETRACK & NOTETEXT - NOTETFORGE AI PLATFORM ⚡
echo          RAG Study & Document Conversion Platform (Hybrid Microservices)
echo ===============================================================================
echo.

:: 1. Check Root Directory
set "ROOT_DIR=%~dp0"
cd /d "%ROOT_DIR%"

:: 2. Check System Prerequisites
echo [*] [1/5] Đang kiểm tra môi trường hệ thống (PHP, Composer, Python, Node, MySQL)...

where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Không tìm thấy PHP trên máy tính hoặc chưa thêm vào System PATH.
    echo Vui lòng cài đặt PHP (XAMPP / Laragon / PHP 8.2+) trước khi tiếp tục.
    pause
    exit /b 1
)

where composer >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [WARNING] Chưa tìm thấy Composer trên PATH. Hãy đảm bảo Composer đã được cài đặt.
)

where python >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Không tìm thấy Python trên máy tính hoặc chưa thêm vào System PATH.
    pause
    exit /b 1
)

where npm >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Không tìm thấy Node.js / npm trên máy tính.
    pause
    exit /b 1
)

echo [OK] Môi trường PHP, Python, Node.js đã sẵn sàng!
echo.

:: 3. Database Initialization & Auto Sync
echo [*] [2/5] Đang khởi tạo và đồng bộ Cơ sở dữ liệu MySQL (noteforge_db)...
where mysql >nul 2>nul
if %ERRORLEVEL% equ 0 (
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS noteforge_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
    mysql -u root noteforge_db < "%ROOT_DIR%database.sql" 2>nul
    echo [OK] Đã kiểm tra & đồng bộ CSDL noteforge_db từ database.sql!
) else (
    echo [INFO] mysql CLI không có trên PATH, sẽ sử dụng Laravel Migration để khởi tạo bảng...
)

:: 4. Backend Dependencies & Migration Check
echo [*] [3/5] Đang kiểm tra và khởi tạo Backend Laravel 11...
cd /d "%ROOT_DIR%backend-laravel"
if not exist "vendor" (
    echo [*] Thư mục vendor chưa có, đang chạy 'composer install'...
    call composer install --no-interaction --prefer-dist
)
if not exist ".env" (
    copy .env.example .env >nul
    call php artisan key:generate
)
call php artisan migrate --force >nul 2>nul
echo [OK] Laravel Backend đã sẵn sàng!
echo.

:: 5. Python AI Engine Dependencies
echo [*] [4/5] Đang kiểm tra Python AI & Document Microservice...
cd /d "%ROOT_DIR%ai-service-python"
if not exist ".env" (
    copy .env.example .env >nul
)
if not exist "venv" (
    echo [*] Đang khởi tạo Python Virtual Environment...
    python -m venv venv
)
call .\venv\Scripts\activate
pip install -r requirements.txt --quiet >nul 2>nul
call deactivate
echo [OK] Python AI Microservice đã sẵn sàng!
echo.

:: 6. Frontend Vue 3 Dependencies
echo [*] [5/5] Đang kiểm tra Frontend Vue 3 SPA...
cd /d "%ROOT_DIR%frontend-vue"
if not exist ".env" (
    copy .env.example .env >nul
)
if not exist "node_modules" (
    echo [*] Đang cài đặt thư viện frontend qua npm install...
    call npm install --silent >nul 2>nul
)
echo [OK] Frontend Vue 3 đã sẵn sàng!
echo.

:: ===============================================================================
:: 7. Khởi chạy đồng thời 4 tiến trình độc lập
:: ===============================================================================
echo ===============================================================================
echo  🚀 ĐANG KHỞI CHẠY TẤT CẢ CÁC PHÂN HỆ HỆ THỐNG:
echo  1. Python AI Service:     http://localhost:8001 (FastAPI + RAG + FFmpeg)
echo  2. Laravel Queue Worker:  Database Driver (Xử lý tác vụ nền & OTP)
echo  3. Laravel API Backend:   http://localhost:8000 (Sanctum + MySQL Gateway)
echo  4. Vue 3 Frontend SPA:    http://localhost:5173 (Host 0.0.0.0 PC + Mobile LAN)
echo ===============================================================================

:: 1. Launch Python AI Service (Port 8001)
start "NoteForge - Python AI Service [8001]" cmd /k "color 0A && cd /d "%ROOT_DIR%ai-service-python" && if exist venv\Scripts\activate (call venv\Scripts\activate) && uvicorn main:app --host 0.0.0.0 --port 8001 --reload"

:: 2. Launch Laravel Queue Worker
start "NoteForge - Laravel Queue Worker" cmd /k "color 0E && cd /d "%ROOT_DIR%backend-laravel" && php artisan queue:work --tries=3"

:: 3. Launch Laravel Backend (Port 8000)
start "NoteForge - Laravel Backend Core [8000]" cmd /k "color 0B && cd /d "%ROOT_DIR%backend-laravel" && php artisan serve --host=0.0.0.0 --port=8000"

:: 4. Launch Vue 3 Frontend (Port 5173 with host enabled for Mobile LAN testing)
start "NoteForge - Vue 3 Frontend SPA [5173]" cmd /k "color 0D && cd /d "%ROOT_DIR%frontend-vue" && npm run dev -- --host 0.0.0.0 --port 5173"

:: Wait 3 seconds and open browser
timeout /t 3 /nobreak >nul
echo.
echo [*] Đang mở trình duyệt web tới http://localhost:5173 ...
start http://localhost:5173

echo.
echo ===============================================================================
echo  ✅ HỆ THỐNG ĐÃ HOẠT ĐỘNG HOÀN TOÀN!
echo  - Để test trên điện thoại thật: Kết nối cùng Wi-Fi và truy cập http://[IP_LAN]:5173
echo  - Để dừng hệ thống: Đóng 4 cửa sổ command prompt tương ứng.
echo ===============================================================================
exit /b 0
