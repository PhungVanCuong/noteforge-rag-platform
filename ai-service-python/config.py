import os
from pathlib import Path
from dotenv import load_dotenv

# Load .env if present
env_path = Path(__file__).resolve().parent / ".env"
load_dotenv(dotenv_path=env_path)

HOST: str = os.getenv("HOST", "0.0.0.0")
PORT: int = int(os.getenv("PORT", "8001"))
GEMINI_API_KEY: str = os.getenv("GEMINI_API_KEY", "")
EMBEDDING_MODEL: str = os.getenv("GEMINI_EMBEDDING_MODEL", os.getenv("EMBEDDING_MODEL", "models/text-embedding-004"))
CHAT_MODEL: str = os.getenv("GEMINI_MODEL", os.getenv("CHAT_MODEL", "gemini-3.8-flash"))

CHROMA_PERSIST_DIR: str = os.getenv(
    "CHROMA_PERSIST_DIR",
    str(Path(__file__).resolve().parent / "vector_db")
)
STORAGE_DIR: str = os.getenv(
    "STORAGE_DIR",
    str(Path(__file__).resolve().parent / "storage")
)

SIMILARITY_THRESHOLD: float = float(os.getenv("SIMILARITY_THRESHOLD", "0.65"))
TOP_K: int = int(os.getenv("TOP_K", "5"))
CHUNK_SIZE: int = int(os.getenv("CHUNK_SIZE", "900"))
CHUNK_OVERLAP: int = int(os.getenv("CHUNK_OVERLAP", "150"))

raw_origins = os.getenv("ALLOWED_ORIGINS", "*")
if raw_origins == "*":
    ALLOWED_ORIGINS = ["*"]
else:
    ALLOWED_ORIGINS = [orig.strip() for orig in raw_origins.split(",") if orig.strip()]

# Ensure directories exist
Path(CHROMA_PERSIST_DIR).mkdir(parents=True, exist_ok=True)
Path(STORAGE_DIR).mkdir(parents=True, exist_ok=True)
Path(STORAGE_DIR, "uploads").mkdir(parents=True, exist_ok=True)
Path(STORAGE_DIR, "conversions").mkdir(parents=True, exist_ok=True)
Path(STORAGE_DIR, "transcripts").mkdir(parents=True, exist_ok=True)
Path(STORAGE_DIR, "tts").mkdir(parents=True, exist_ok=True)
