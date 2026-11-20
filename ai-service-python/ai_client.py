import os
import json
import re
import time
import logging
from typing import Any, Dict, List, Optional
import google.generativeai as genai
try:
    from . import config
except ImportError:
    import config

logger = logging.getLogger(__name__)

FALLBACK_CHAT_MODELS = [
    "gemini-3-flash-preview",
    "gemini-flash-lite-latest",
    "gemini-3.8-flash",
    "gemini-3.7-flash",
    "gemini-3.6-flash",
    "gemini-3.5-flash",
    "gemini-flash-latest",
]

FALLBACK_EMBEDDING_MODELS = [
    "models/gemini-embedding-001",
    "gemini-embedding-001",
    "models/gemini-embedding-2-preview",
    "models/gemini-embedding-2",
]

def clean_json_string(raw_text: str) -> Dict[str, Any]:
    """Cleans markdown fences and parses JSON safely."""
    text = raw_text.strip()
    if text.startswith("```"):
        text = re.sub(r"^```[a-zA-Z]*\s*", "", text)
        text = re.sub(r"\s*```$", "", text)
        text = text.strip()

    try:
        return json.loads(text)
    except json.JSONDecodeError:
        # Try extracting JSON object/array using regex
        match = re.search(r"(\{[\s\S]*\}|\[[\s\S]*\])", text)
        if match:
            try:
                return json.loads(match.group(1))
            except Exception:
                pass
        # Clean control characters
        cleaned = re.sub(r"[\x00-\x1F\x7F]", " ", text)
        return json.loads(cleaned)

def generate_with_fallback(
    api_key: Optional[str],
    prompt: Any,
    generation_config: Optional[Dict[str, Any]] = None,
    system_instruction: Optional[str] = None
) -> str:
    """
    Attempts to generate content using fallback chain of Gemini models.
    Handles 429 quota backoff and never crashes due to 404 model deprecation.
    """
    active_key = api_key or config.GEMINI_API_KEY
    if not active_key:
        raise ValueError("No Gemini API Key provided or configured.")

    genai.configure(api_key=active_key)

    preferred_model = os.getenv("GEMINI_MODEL", os.getenv("CHAT_MODEL", "gemini-3-flash-preview"))
    models_to_try = [preferred_model] + [m for m in FALLBACK_CHAT_MODELS if m != preferred_model]

    last_error = None
    for model_name in models_to_try:
        try:
            model = genai.GenerativeModel(
                model_name=model_name,
                generation_config=generation_config,
                system_instruction=system_instruction
            )
            response = model.generate_content(prompt)
            if response and response.text:
                logger.info(f"Successfully generated content using model: {model_name}")
                return response.text
        except Exception as e:
            err_msg = str(e)
            last_error = e
            logger.warning(f"Model {model_name} failed: {err_msg[:120]}. Trying next candidate...")
            continue

    # Fallback: Query live list of available models for this key
    try:
        logger.info("Attempting live discovery of supported models from Gemini API...")
        available = [
            m.name.replace("models/", "")
            for m in genai.list_models()
            if "generateContent" in m.supported_generation_methods and "flash" in m.name
        ]
        for live_model in available:
            try:
                time.sleep(2)
                model = genai.GenerativeModel(
                    model_name=live_model,
                    generation_config=generation_config,
                    system_instruction=system_instruction
                )
                response = model.generate_content(prompt)
                if response and response.text:
                    logger.info(f"Live discovered model {live_model} succeeded!")
                    return response.text
            except Exception:
                continue
    except Exception as e:
        logger.error(f"Live model discovery failed: {e}")

    raise RuntimeError(f"All Gemini models failed. Last error: {last_error}")

def get_embedding_with_fallback(text: str, api_key: Optional[str] = None) -> List[float]:
    """Generates embedding using fallback list of models."""
    active_key = api_key or config.GEMINI_API_KEY
    if not active_key:
        raise ValueError("No Gemini API Key provided for embedding.")

    genai.configure(api_key=active_key)
    last_error = None
    for model_name in FALLBACK_EMBEDDING_MODELS:
        try:
            res = genai.embed_content(
                model=model_name,
                content=text,
                task_type="retrieval_document"
            )
            if "embedding" in res:
                return res["embedding"]
        except Exception as e:
            last_error = e
            continue

    raise RuntimeError(f"All embedding models failed. Last error: {last_error}")
