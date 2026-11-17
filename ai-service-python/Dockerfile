FROM python:3.11-slim

WORKDIR /app

# Install system dependencies: LibreOffice headless, FFmpeg, fonts for Vietnamese
RUN apt-get update && apt-get install -y --no-install-recommends \
    libreoffice \
    libreoffice-writer \
    libreoffice-calc \
    libreoffice-impress \
    ffmpeg \
    fonts-liberation \
    fonts-dejavu \
    curl \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

EXPOSE 8001

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8001"]
