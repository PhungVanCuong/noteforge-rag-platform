import os
import uuid
import logging
from pathlib import Path
from typing import Optional, Dict, Any
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

# Edge TTS voice map
EDGE_VOICE_MAP = {
    "vi": "vi-VN-HoaiMyNeural",
    "vi-VN": "vi-VN-HoaiMyNeural",
    "en": "en-US-JennyNeural",
    "en-US": "en-US-JennyNeural",
    "en-GB": "en-GB-SoniaNeural",
    "ja": "ja-JP-NanamiNeural",
    "ko": "ko-KR-SunHiNeural",
    "zh": "zh-CN-XiaoxiaoNeural",
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
}

class TtsService:
    @staticmethod
    def detect_language(text: str) -> str:
        """Simple heuristic to detect Vietnamese vs English vs others."""
        vietnamese_chars = set("àáảãạăằắẳẵặâầấẩẫậèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđĐ")
        for char in text.lower():
            if char in vietnamese_chars:
                return "vi"
        return "en"

    @classmethod
    async def synthesize_speech(
        cls,
        text: str,
        lang: str = "auto",
        voice: Optional[str] = None,
        rate: float = 1.0
    ) -> Dict[str, Any]:
        """
        Synthesizes speech to MP3. Tries edge-tts first, falls back to gTTS.
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

        # Format rate for edge_tts (+0%, +20%, -10%)
        rate_diff = int((rate - 1.0) * 100)
        rate_str = f"+{rate_diff}%" if rate_diff >= 0 else f"{rate_diff}%"

        selected_voice = voice or EDGE_VOICE_MAP.get(target_lang, EDGE_VOICE_MAP.get(base_lang, "en-US-JennyNeural"))

        # Try Edge-TTS
        try:
            communicate = edge_tts.Communicate(clean_text, selected_voice, rate=rate_str)
            await communicate.save(str(out_path))
            if out_path.exists() and out_path.stat().st_size > 0:
                return {
                    "file_name": file_id,
                    "file_path": str(out_path),
                    "url": f"/api/tts/audio/{file_id}",
                    "lang": target_lang,
                    "engine": "edge-tts"
                }
        except Exception as e:
            logger.warning(f"Edge-TTS failed: {e}. Falling back to gTTS...")

        # Fallback to gTTS
        try:
            gtts_lang = GTTS_LANG_MAP.get(target_lang, GTTS_LANG_MAP.get(base_lang, "en"))
            tts = gTTS(text=clean_text, lang=gtts_lang, slow=(rate < 0.85))
            tts.save(str(out_path))
            return {
                "file_name": file_id,
                "file_path": str(out_path),
                "url": f"/api/tts/audio/{file_id}",
                "lang": target_lang,
                "engine": "gTTS"
            }
        except Exception as e:
            logger.error(f"gTTS failed: {e}")
            raise RuntimeError(f"Không thể tổng hợp giọng đọc: {str(e)}")
