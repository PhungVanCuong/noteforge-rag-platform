@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion
title NoteForge AI - Unified One-Click Launcher
color 0B

echo ===============================================================================
echo                NOTETRACK and NOTETEXT - NOTETFORGE AI PLATFORM
echo          RAG Study and Document Conversion Platform [Hybrid Microservices]
echo ===============================================================================
echo.

REM 1. Set Root Directory
set "ROOT_DIR=%~dp0"
cd /d "%ROOT_DIR%"

REM 2. Check System Prerequisites
echo [*] [1/5] Kiem tra moi truong he thong: PHP, Composer, Python, Node, MySQL...

where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Khong tim thay PHP tren he thong hoac chua them vao PATH.
    echo Vui long bat XAMPP hoac cai dat PHP 8.2+ truoc khi tiep tuc.
    pause
    exit /b 1
)

where composer >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [WARNING] Chua tim thay Composer tren PATH.
)

where python >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Khong tim thay Python tren he thong hoac chua them vao PATH.
    pause
    exit /b 1
)

where npm >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Khong tim thay Node.js / npm tren he thong.
    pause
    exit /b 1
)

echo [OK] Moi truong PHP, Python, Node.js da san sang!
echo.

REM 3. Database Initialization and Auto Sync
echo [*] [2/5] Kiem tra va dong bo Co so du lieu MySQL [noteforge_db]...
where mysql >nul 2>nul
if %ERRORLEVEL% equ 0 (
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS noteforge_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul
    echo [OK] Co so du lieu noteforge_db da san sang!
) else (
    echo [INFO] mysql CLI khong co tren PATH, Laravel Migration se tu dong xu ly...
)

REM 4. Backend Dependencies and Migration
echo [*] [3/5] Kiem tra va khoi tao Backend Laravel 11...
cd /d "%ROOT_DIR%backend-laravel"
if not exist "vendor" (
    echo [*] Thu muc vendor chua co, dang chay composer install...
    call composer install --no-interaction --prefer-dist
)
if not exist ".env" (
    copy .env.example .env >nul
    call php artisan key:generate
)
call php artisan migrate --force
call php artisan db:seed --force
call php artisan optimize:clear >nul 2>nul
echo [OK] Laravel Backend va CSDL da duoc dong bo san sang!
echo.

REM 5. Python AI Engine Dependencies
echo [*] [4/5] Kiem tra Python AI and Document Microservice...
cd /d "%ROOT_DIR%ai-service-python"
if not exist ".env" (
    copy .env.example .env >nul
)
if not exist "venv" (
    echo [*] Dang tao Python Virtual Environment...
    python -m venv venv
)
if exist "venv\Scripts\activate.bat" (
    call venv\Scripts\activate.bat
    pip install -r requirements.txt --quiet >nul 2>nul
    call deactivate
)
echo [OK] Python AI Microservice da san sang!
echo.

REM 6. Frontend Vue 3 Dependencies
echo [*] [5/5] Kiem tra Frontend Vue 3 SPA...
cd /d "%ROOT_DIR%frontend-vue"
if not exist ".env" (
    copy .env.example .env >nul
)
if not exist "node_modules" (
    echo [*] Dang cai dat thu vien frontend qua npm install...
    call npm install --silent >nul 2>nul
)
echo [OK] Frontend Vue 3 da san sang!
echo.

REM ===============================================================================
REM 7. Launch All 4 Services Concurrently
REM ===============================================================================
echo ===============================================================================
echo   DANG KHOI CHAY TAT CA CAC PHAN HE HE THONG:
echo   1. Python AI Service:     http://localhost:8001 [FastAPI + RAG + FFmpeg]
echo   2. Laravel Queue Worker:  Database Driver [Xu ly tac vu nen va OTP]
echo   3. Laravel API Backend:   http://localhost:8000 [Sanctum + MySQL Gateway]
echo   4. Vue 3 Frontend SPA:    http://localhost:5173 [Host 0.0.0.0 PC + Mobile LAN]
echo ===============================================================================

REM 1. Launch Python AI Service
start "NoteForge - Python AI [8001]" /D "%ROOT_DIR%ai-service-python" cmd /k "color 0A & if exist venv\Scripts\activate.bat (call venv\Scripts\activate.bat) & uvicorn main:app --host 0.0.0.0 --port 8001 --reload"

REM 2. Launch Laravel Queue Worker
start "NoteForge - Laravel Queue Worker" /D "%ROOT_DIR%backend-laravel" cmd /k "color 0E & php artisan queue:work --tries=3"

REM 3. Launch Laravel Backend Core
start "NoteForge - Laravel Backend [8000]" /D "%ROOT_DIR%backend-laravel" cmd /k "color 0B & php artisan serve --host 0.0.0.0 --port 8000"

REM 4. Launch Vue 3 Frontend SPA
start "NoteForge - Vue 3 Frontend [5173]" /D "%ROOT_DIR%frontend-vue" cmd /k "color 0D & npm run dev -- --host 0.0.0.0 --port 5173"

REM Wait 3 seconds and open browser
ping 127.0.0.1 -n 4 >nul
echo.
echo [*] Dang mo trinh duyet web tai http://localhost:5173 ...
start http://localhost:5173

echo.
echo ===============================================================================
echo   HE THONG DA HOAT DONG HOAN TOAN!
echo   - Truy cap tren may tinh: http://localhost:5173
echo   - De dung he thong: Dong cac cua so console tuong ung.
echo ===============================================================================
exit /b 0
