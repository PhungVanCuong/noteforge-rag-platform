import os
import uuid
import logging
from pathlib import Path
from typing import Optional, Dict, Any, List
from gtts import gTTS
import edge_tts
import asyncio

try:
    from . import config
except ImportError:
    import config

logger = logging.getLogger(__name__)

TTS_OUTPUT_DIR = Path(config.STORAGE_DIR) / "tts"
TTS_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# Curated high-performance Microsoft Edge Neural Voices
VOICE_CATALOG = [
    {
        "id": "vi-VN-HoaiMyNeural",
        "name": "Hoài My (Nữ VN - Chuẩn Song Ngữ Anh-Việt)",
        "lang": "vi-VN",
        "gender": "Female",
        "badge": "Song Ngữ ⭐"
    },
    {
        "id": "vi-VN-NamMinhNeural",
        "name": "Nam Minh (Nam VN - Trầm Ấm, Truyền Cảm)",
        "lang": "vi-VN",
        "gender": "Male",
        "badge": "Song Ngữ ⭐"
    },
    {
        "id": "en-US-JennyNeural",
        "name": "Jenny (Nữ US - Tự Nhiên, Trôi Chảy)",
        "lang": "en-US",
        "gender": "Female",
        "badge": "Tiếng Anh Mỹ"
    },
    {
        "id": "en-US-GuyNeural",
        "name": "Guy (Nam US - Chuẩn Giảng Viên)",
        "lang": "en-US",
        "gender": "Male",
        "badge": "Tiếng Anh Mỹ"
    },
    {
        "id": "en-US-AriaNeural",
        "name": "Aria (Nữ US - Thuyết Trình Tin Tức)",
        "lang": "en-US",
        "gender": "Female",
        "badge": "Tiếng Anh Mỹ"
    },
    {
        "id": "en-GB-SoniaNeural",
        "name": "Sonia (Nữ UK - Giọng Anh-Anh Quý Tộc)",
        "lang": "en-GB",
        "gender": "Female",
        "badge": "Tiếng Anh Anh"
    },
    {
        "id": "ja-JP-NanamiNeural",
        "name": "Nanami (Nữ Nhật Bản)",
        "lang": "ja-JP",
        "gender": "Female",
        "badge": "Tiếng Nhật"
    },
    {
        "id": "ko-KR-SunHiNeural",
        "name": "Sun-Hi (Nữ Hàn Quốc)",
        "lang": "ko-KR",
        "gender": "Female",
        "badge": "Tiếng Hàn"
    },
    {
        "id": "zh-CN-XiaoxiaoNeural",
        "name": "Xiaoxiao (Nữ Trung Quốc)",
        "lang": "zh-CN",
        "gender": "Female",
        "badge": "Tiếng Trung"
    },
    {
        "id": "fr-FR-DeniseNeural",
        "name": "Denise (Nữ Pháp)",
        "lang": "fr-FR",
        "gender": "Female",
        "badge": "Tiếng Pháp"
    },
]

EDGE_VOICE_MAP = {
    "vi": "vi-VN-HoaiMyNeural",
    "vi-VN": "vi-VN-HoaiMyNeural",
    "en": "en-US-JennyNeural",
    "en-US": "en-US-JennyNeural",
    "en-GB": "en-GB-SoniaNeural",
    "ja": "ja-JP-NanamiNeural",
    "ko": "ko-KR-SunHiNeural",
    "zh": "zh-CN-XiaoxiaoNeural",
    "fr": "fr-FR-DeniseNeural",
}

GTTS_LANG_MAP = {
    "vi": "vi",
    "vi-VN": "vi",
    "en": "en",
    "en-US": "en",
    "en-GB": "en",
    "ja": "ja",
    "ko": "ko",
    "zh": "zh-CN",
    "fr": "fr",
}

class TtsService:
    @staticmethod
    def get_voices() -> List[Dict[str, Any]]:
        return VOICE_CATALOG

    @staticmethod
    def detect_language(text: str) -> str:
        """Heuristic to detect Vietnamese vs other languages."""
        vietnamese_chars = set("àáảãạăằắẳẵặâầấẩẫậèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđĐ")
        for char in text.lower():
            if char in vietnamese_chars:
                return "vi-VN"
        return "en-US"

    @classmethod
    async def synthesize_speech(
        cls,
        text: str,
        lang: str = "auto",
        voice: Optional[str] = None,
        rate: float = 1.0
    ) -> Dict[str, Any]:
        """
        Synthesizes speech to MP3 using Microsoft Edge TTS (edge-tts).
        Seamlessly reads mixed Vietnamese and English code-switching.
        Falls back to gTTS if Edge TTS is unavailable.
        """
        clean_text = text.strip()
        if not clean_text:
            raise ValueError("Nội dung văn bản không được để trống.")

        # Resolve language
        target_lang = lang
        if target_lang == "auto" or not target_lang:
            target_lang = cls.detect_language(clean_text)

        base_lang = target_lang.split("-")[0].lower()
        file_id = f"tts_{uuid.uuid4().hex[:12]}.mp3"
        out_path = TTS_OUTPUT_DIR / file_id

        # Format rate for edge_tts (+0%, +25%, -15%)
        rate_diff = int(round((rate - 1.0) * 100))
        rate_str = f"+{rate_diff}%" if rate_diff >= 0 else f"{rate_diff}%"

        selected_voice = voice or EDGE_VOICE_MAP.get(target_lang, EDGE_VOICE_MAP.get(base_lang, "vi-VN-HoaiMyNeural"))

        # Primary: Edge-TTS
        try:
            communicate = edge_tts.Communicate(clean_text, selected_voice, rate=rate_str)
            await communicate.save(str(out_path))
            if out_path.exists() and out_path.stat().st_size > 0:
                file_size = out_path.stat().st_size
                return {
                    "file_name": file_id,
                    "file_path": str(out_path),
                    "file_size": file_size,
                    "url": f"/api/tts/audio/{file_id}",
                    "lang": target_lang,
                    "voice": selected_voice,
                    "rate": rate,
                    "engine": "edge-tts"
                }
        except Exception as e:
            logger.warning(f"Edge-TTS failed: {e}. Falling back to gTTS...")

        # Fallback: gTTS
        try:
            gtts_lang = GTTS_LANG_MAP.get(target_lang, GTTS_LANG_MAP.get(base_lang, "vi"))
            tts = gTTS(text=clean_text, lang=gtts_lang, slow=(rate < 0.85))
            tts.save(str(out_path))
            return {
                "file_name": file_id,
                "file_path": str(out_path),
                "file_size": out_path.stat().st_size,
                "url": f"/api/tts/audio/{file_id}",
                "lang": target_lang,
                "voice": "gTTS-standard",
                "rate": rate,
                "engine": "gTTS"
            }
        except Exception as e:
            logger.error(f"gTTS failed: {e}")
            raise RuntimeError(f"Không thể tổng hợp giọng đọc: {str(e)}")
