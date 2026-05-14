from __future__ import annotations

import logging
import os
import time
from pathlib import Path
from typing import Dict, List

import chromadb
import google.generativeai as genai
from chromadb.config import Settings
from dotenv import load_dotenv
from sentence_transformers import SentenceTransformer

from reranker import rerank_chunks
from verifier import Verifier

BASE_DIR = Path(__file__).resolve().parent
CHROMA_DIR = BASE_DIR / "chroma_db"
COLLECTION_NAME = "recycling"
EMBEDDING_MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
UNKNOWN_ANSWER = "I don't know based on the current knowledge base."

load_dotenv(BASE_DIR / ".env")
logger = logging.getLogger("notun_alo_rag")

_last_retrieved_chunks: List[Dict] = []
_embedding_model = None
_collection = None


def get_embedding_model():
    global _embedding_model
    if _embedding_model is None:
        _embedding_model = SentenceTransformer(EMBEDDING_MODEL_NAME, local_files_only=True)
    return _embedding_model


def get_collection():
    global _collection
    if _collection is None:
        client = chromadb.PersistentClient(path=str(CHROMA_DIR), settings=Settings(anonymized_telemetry=False))
        _collection = client.get_or_create_collection(COLLECTION_NAME)
    return _collection


def detect_language(text: str, requested: str | None = None) -> str:
    if requested in {"bn", "en"}:
        return requested
    return "bn" if any("\u0980" <= char <= "\u09ff" for char in text) else "en"


def retrieve_chunks(query: str, top_k: int = 8) -> List[Dict]:
    started = time.perf_counter()
    model = get_embedding_model()
    collection = get_collection()
    query_embedding = model.encode([query], show_progress_bar=False)[0].tolist()
    result = collection.query(query_embeddings=[query_embedding], n_results=top_k, include=["documents", "metadatas", "distances"])
    chunks: List[Dict] = []
    docs = result.get("documents", [[]])[0]
    metas = result.get("metadatas", [[]])[0]
    distances = result.get("distances", [[]])[0]
    for index, document in enumerate(docs):
        metadata = metas[index] if index < len(metas) else {}
        chunks.append({"text": document, "metadata": metadata, "distance": distances[index] if index < len(distances) else None})
    chunks = rerank_chunks(chunks)
    logger.info("Retrieved %s chunks in %.1fms for query: %s", len(chunks), (time.perf_counter() - started) * 1000, query[:120])
    return chunks


def build_prompt(query: str, chunks: List[Dict], language: str) -> str:
    context_parts = []
    for idx, chunk in enumerate(chunks, start=1):
        meta = chunk.get("metadata", {})
        label = f"Source {idx}: {meta.get('filename', 'unknown')} page {meta.get('page_number', 1)}"
        context_parts.append(f"[{label}]\n{chunk.get('text', '')}")
    context = "\n\n---\n\n".join(context_parts)
    answer_language = "Bangla" if language == "bn" else "English"
    return f"""You are Notun Alo's multilingual recycling and sustainability assistant.

Answer using ONLY the provided context. If the answer is not found in the context, say exactly:
{UNKNOWN_ANSWER}

Rules:
- Answer primarily in {answer_language}.
- Preserve English technical terms when useful.
- Do not invent facts, numbers, laws, organizations, or recommendations.
- Keep the answer concise and helpful for a hackathon demo.
- Do not schedule pickups or modify user data.

CONTEXT:
{context}

USER QUESTION:
{query}

ANSWER:
"""


def format_sources(chunks: List[Dict]) -> List[Dict]:
    seen = set()
    sources = []
    for chunk in chunks:
        meta = chunk.get("metadata", {})
        key = (meta.get("source_doc"), meta.get("page_number"))
        if key in seen:
            continue
        seen.add(key)
        sources.append({
            "filename": meta.get("filename", "unknown"),
            "page_number": meta.get("page_number", 1),
            "source_doc": meta.get("source_doc", ""),
            "folder_category": meta.get("folder_category", ""),
            "file_type": meta.get("file_type", ""),
        })
    return sources


def fallback_answer(query: str, chunks: List[Dict], language: str) -> str:
    if not chunks:
        return UNKNOWN_ANSWER
    best = chunks[0].get("text", "").strip()
    if not best:
        return UNKNOWN_ANSWER
    snippet = best[:900].rsplit(" ", 1)[0]
    if language == "bn":
        return "বর্তমান knowledge base অনুযায়ী পাওয়া তথ্য:\n" + snippet
    return "Based on the current knowledge base:\n" + snippet


def generate_answer(query: str, chunks: List[Dict], language: str) -> str:
    api_key = os.getenv("GEMINI_API_KEY", "").strip()
    if not chunks:
        return UNKNOWN_ANSWER
    if not api_key:
        logger.info("GEMINI_API_KEY missing; using local fallback answer.")
        return fallback_answer(query, chunks, language)
    genai.configure(api_key=api_key)
    model_name = os.getenv("GEMINI_MODEL", "gemini-2.5-flash").strip() or "gemini-2.5-flash"
    model = genai.GenerativeModel(model_name)
    response = model.generate_content(build_prompt(query, chunks, language), generation_config={"temperature": 0.2, "max_output_tokens": 900})
    answer = (getattr(response, "text", "") or "").strip()
    return answer or UNKNOWN_ANSWER


def answer_query(query: str, language: str | None = None, top_k: int = 8) -> Dict:
    global _last_retrieved_chunks
    query = (query or "").strip()
    if not query:
        return {"answer": "Please enter a question.", "sources": [], "chunks": []}
    lang = detect_language(query, language)
    chunks = retrieve_chunks(query, top_k=top_k)
    _last_retrieved_chunks = chunks
    answer = generate_answer(query, chunks, lang)
    verification = Verifier().verify(answer, chunks)
    sources = [] if answer.strip() == UNKNOWN_ANSWER else format_sources(chunks)
    return {"answer": answer, "sources": sources, "verification": verification, "chunks": chunks}


def latest_chunks() -> List[Dict]:
    return _last_retrieved_chunks
