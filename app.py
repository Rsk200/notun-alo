from __future__ import annotations

import logging
from logging.handlers import RotatingFileHandler
from pathlib import Path

from dotenv import load_dotenv
from flask import Flask, jsonify, request
from flask_cors import CORS
from werkzeug.utils import secure_filename

from ingest import SUPPORTED_EXTENSIONS, UPLOADS_DIR, ingest_documents
from rag_pipeline import UNKNOWN_ANSWER_EN, answer_query, latest_chunks
import os
os.environ["NO_PROXY"] = "*"
os.environ["no_proxy"] = "*"
os.environ["HF_HUB_OFFLINE"] = "1"
os.environ["TRANSFORMERS_OFFLINE"] = "1"

BASE_DIR = Path(__file__).resolve().parent
LOG_DIR = BASE_DIR / "logs"
LOG_DIR.mkdir(exist_ok=True)
UPLOADS_DIR.mkdir(exist_ok=True)
load_dotenv(BASE_DIR / ".env")


def setup_logging() -> logging.Logger:
    logger = logging.getLogger("notun_alo_rag")
    logger.setLevel(logging.INFO)
    logger.propagate = False
    if not logger.handlers:
        handler = RotatingFileHandler(LOG_DIR / "rag.log", maxBytes=1_000_000, backupCount=5, encoding="utf-8")
        handler.setFormatter(logging.Formatter("%(asctime)s [%(levelname)s] %(message)s"))
        logger.addHandler(handler)
    return logger


logger = setup_logging()
app = Flask(__name__)
CORS(app)


def json_error(message: str, status: int = 400):
    logger.error(message)
    return jsonify({"error": message}), status


@app.get("/health")
def health():
    return jsonify({"status": "ok", "service": "notun-alo-rag", "collection": "recycling", "unknown_answer": UNKNOWN_ANSWER_EN})


@app.post("/upload")
def upload():
    try:
        if "file" not in request.files:
            return json_error("No file field was provided.")
        file = request.files["file"]
        if not file.filename:
            return json_error("No file was selected.")
        suffix = Path(file.filename).suffix.lower()
        if suffix not in SUPPORTED_EXTENSIONS:
            return json_error(f"Unsupported file type: {suffix}")
        safe_name = secure_filename(file.filename) or f"upload{suffix}"
        destination = UPLOADS_DIR / safe_name
        file.save(destination)
        logger.info("Saved upload: %s", destination)
        summary = ingest_documents(only_file=destination)
        return jsonify({"message": "File uploaded and indexed.", "file": safe_name, "ingestion": summary})
    except Exception as exc:
        logger.exception("Upload failed: %s", exc)
        return json_error("Upload failed. Check logs/rag.log for details.", 500)


@app.post("/ingest")
def ingest():
    try:
        payload = request.get_json(silent=True) or {}
        summary = ingest_documents(rebuild=bool(payload.get("rebuild", False)))
        return jsonify(summary)
    except Exception as exc:
        logger.exception("Ingestion endpoint failed: %s", exc)
        return json_error("Ingestion failed. Check logs/rag.log for details.", 500)


@app.post("/chat")
def chat():
    try:
        payload = request.get_json(silent=True) or {}
        query = str(payload.get("query", "")).strip()
        language = payload.get("language")
        user_name = str(payload.get("user_name", "")).strip()
        user_points = int(payload.get("user_points", 0))
        if not query:
            return json_error("Query is required.")
        result = answer_query(query=query, language=language, user_name=user_name, user_points=user_points)
        return jsonify({"answer": result["answer"], "sources": result["sources"], "verification": result.get("verification", {"score": 1.0})})
    except Exception as exc:
        logger.exception("Chat endpoint failed: %s", exc)
        return json_error("Chat failed. Check logs/rag.log for details.", 500)


@app.get("/sources")
def sources():
    safe_chunks = []
    for chunk in latest_chunks():
        safe_chunks.append({"text": chunk.get("text", "")[:1000], "metadata": chunk.get("metadata", {}), "distance": chunk.get("distance")})
    return jsonify({"chunks": safe_chunks})


if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000, debug=True)
