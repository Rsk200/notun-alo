from __future__ import annotations

import logging
import os
import time
from pathlib import Path
from typing import Dict, List, Optional

import chromadb
import google.generativeai as genai
from chromadb.config import Settings
from dotenv import load_dotenv
from sentence_transformers import SentenceTransformer

from reranker import rerank_chunks
from verifier import Verifier

os.environ["NO_PROXY"] = "*"
os.environ["no_proxy"] = "*"
os.environ["HF_HUB_OFFLINE"] = "1"

BASE_DIR = Path(__file__).resolve().parent
CHROMA_DIR = BASE_DIR / "chroma_db"
COLLECTION_NAME = "recycling"
EMBEDDING_MODEL_NAME = "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2"
UNKNOWN_ANSWER_EN = "I couldn't find specific details on that in my current records, but I can help with recycling info, points, or pickups! 🌿"
UNKNOWN_ANSWER_BN = "এই বিষয়ে আমার কাছে এখন পর্যাপ্ত তথ্য নেই, তবে আমি আপনাকে রিসাইক্লিং, পয়েন্ট বা পিকআপ নিয়ে সাহায্য করতে পারি। 🌿"

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
    if any("\u0980" <= char <= "\u09ff" for char in text):
        return "bn"
    if requested in {"bn", "en"}:
        return requested
    banglish_keywords = {"ki", "obostha", "kemon", "acho", "ami", "tumi", "khobor", "valobashi", "dhaka", "bangla", "bhalo"}
    words = text.lower().split()
    if any(word in banglish_keywords for word in words):
        return "bn"
    return "en"


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


def build_prompt(query: str, chunks: List[Dict], language: str, user_name: str = "", user_points: int = 0) -> str:
    context_parts = []
    for idx, chunk in enumerate(chunks, start=1):
        meta = chunk.get("metadata", {})
        label = f"Source {idx}: {meta.get('filename', 'unknown')}"
        context_parts.append(f"[{label}]\n{chunk.get('text', '')}")
    context = "\n\n---\n\n".join(context_parts)
    answer_language = "Bangla" if language == "bn" else "English"

    user_info = f"USER: {user_name or 'Guest'}"
    if user_points:
        user_info += f" | POINTS: {user_points}"

    return f"""You are Notun Alo (নতুন আলো), a highly intelligent and natural conversational AI. 
Your goal is to be a smooth, human-like, and emotionally adaptive guide.

[IDENTITY]
User: {user_name or 'Friend'} ({user_points} pts)

[KNOWLEDGE SOURCE]
{context}

[CONVERSATIONAL RULES (STRICT)]
1. **Natural Multilingualism**: 
   - If User speaks Bangla -> Reply fully in natural Bangla.
   - If User speaks English -> Reply fully in clean English.
   - NEVER mix languages unnaturally in one sentence (e.g., "I understand আপনি").
   - Understand Banglish (transliteration) perfectly and reply in natural Bangla.
2. **Anti-Robotic**: 
   - NEVER say "I am an AI assistant" or "I am still learning". 
   - If you don't know something, say "এই বিষয়ে আমার কাছে এখন পর্যাপ্ত তথ্য নেই" or "I couldn't find reliable info on that yet."
   - Avoid overly formal/textbook language. Use modern, conversational tones.
3. **Conciseness**: 
   - Keep casual greetings short (e.g., "ভালো আছি 😊 আপনি কেমন আছেন?").
   - Don't overexplain simple questions. Answer the intent, then stop.
4. **Emotional Flow**: 
   - Be empathetic if the user is frustrated. Be warm and supportive.
   - Use exactly one relevant emoji (♻️, 🌿, 😊).
5. **RAG Integration**: 
   - Summarize knowledge base info conversationally. Avoid raw data dumps.
   - Stay factually accurate based on the context provided.

USER: {query}

[NOTUN ALO]:
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


def is_greeting(query: str) -> str | None:
    q = query.lower().strip()
    en_greetings = {"hi", "hello", "hey", "good morning", "good evening", "good afternoon"}
    bn_greetings = {"সালাম", "নমস্কার", "কেমন আছেন", "কি খবর", "ki obostha", "kemon acho", "হ্যালো", "হাই"}
    if any(g in q for g in en_greetings) or len(q) < 4:
        return "en"
    if any(g in q for g in bn_greetings):
        return "bn"
    return None


def fallback_answer(query: str, chunks: List[Dict], language: str, user_name: str = "", user_points: int = 0) -> str:
    greeting_lang = is_greeting(query)
    if greeting_lang:
        if greeting_lang == "bn":
            return f"হ্যালো {user_name or ''}! 😊 আমি Notun Alo সহকারী। আমি আপনাকে রিসাইক্লিং তথ্য, পয়েন্ট চেক বা পিকআপ শিডিউল করতে সাহায্য করতে পারি।"
        return f"Hello {user_name or ''}! 😊 I'm your Notun Alo assistant. I can help with recycling info, point checks, or pickups."

    q = query.lower().strip()
    points_keywords = ["point", "পয়েন্ট", "পয়েন্ট", "reward", "রিওয়ার্ড", "balance", "ব্যালেন্স", "কত"]
    if any(kw in q for kw in points_keywords):
        if language == "bn":
            return f"🏆 আপনার বর্তমান ব্যালেন্স: **{user_points} পয়েন্ট**। আপনি কাগজ, প্লাস্টিক এবং ধাতু রিসাইক্লিং করে আরও পয়েন্ট অর্জন করতে পারেন। 😊"
        return f"🏆 Your current balance is **{user_points} points**. You can earn more by recycling Paper, Plastic, and Metal. 😊"

    if not chunks:
        return UNKNOWN_ANSWER_BN if language == "bn" else UNKNOWN_ANSWER_EN

    # Smart snippet extraction
    query_words = [w for w in q.split() if len(w) > 2]
    best_chunk = chunks[0]
    max_matches = 0
    for chunk in chunks:
        text = chunk.get("text", "").lower()
        matches = sum(1 for word in query_words if word in text)
        if matches > max_matches:
            max_matches = matches
            best_chunk = chunk

    snippet = best_chunk.get("text", "").strip()
    filename = best_chunk.get("metadata", {}).get("filename", "document")

    if language == "bn":
        return f"আমি এই তথ্যটি খুঁজে পেয়েছি:\n{snippet[:500]}..."
    return f"I found this in my records:\n{snippet[:500]}..."


def call_free_llm(query: str, chunks: List[Dict], language: str) -> str | None:
    try:
        import requests
        prompt = build_prompt(query, chunks, language)
        payload = {
            "messages": [
                {"role": "system", "content": "You are Notun Alo's helpful recycling assistant. Use the provided context to answer."},
                {"role": "user", "content": prompt}
            ],
            "model": "openai",
            "private": True
        }
        resp = requests.post("https://text.pollinations.ai/openai", json=payload, timeout=10)
        if resp.status_code == 200:
            data = resp.json()
            return data.get("choices", [{}])[0].get("message", {}).get("content", "").strip() or None
    except Exception as e:
        logger.debug("Free LLM fallback failed: %s", e)
    return None


def generate_answer(query: str, chunks: List[Dict], language: str, user_name: str = "", user_points: int = 0) -> str:
    api_key = os.getenv("GEMINI_API_KEY", "").strip()
    if not chunks and not is_greeting(query):
        return UNKNOWN_ANSWER_BN if language == "bn" else UNKNOWN_ANSWER_EN

    if api_key:
        genai.configure(api_key=api_key)
        model_name = (os.getenv("GEMINI_MODEL", "gemini-1.5-flash").strip()) or "gemini-1.5-flash"
        model = genai.GenerativeModel(model_name)
        prompt = build_prompt(query, chunks, language, user_name, user_points)
        response = model.generate_content(prompt, generation_config={"temperature": 0.2, "max_output_tokens": 900})
        answer = (getattr(response, "text", "") or "").strip()
        if answer:
            return answer

    logger.info("Gemini unavailable; trying free LLM fallback.")
    free_answer = call_free_llm(query, chunks, language)
    if free_answer:
        return free_answer

    return fallback_answer(query, chunks, language, user_name, user_points)


def answer_query(query: str, language: str | None = None, top_k: int = 8, user_name: str = "", user_points: int = 0) -> Dict:
    global _last_retrieved_chunks
    query = (query or "").strip()
    if not query:
        return {"answer": "Please enter a question.", "sources": [], "chunks": []}
    
    lang = detect_language(query, language)
    
    # ─── Small Talk / Identity Logic ───
    q_low = query.lower()
    identity_keywords = ["your name", "who are you", "my name", "who am i", "amar name", "tumi ke", "tomar name", "আমাকে চেনো", "তোমার নাম"]
    if any(k in q_low for k in identity_keywords):
        if lang == "bn":
            return {"answer": f"আমি Notun Alo (নতুন আলো)। আমি জানি আপনি **{user_name or 'আমাদের বন্ধু'}**, এবং আপনার **{user_points} পয়েন্ট** আছে! 😊", "sources": [], "verification": {"score": 1.0}, "chunks": []}
        return {"answer": f"I am Notun Alo. I know you are **{user_name or 'Friend'}**, and you have **{user_points} points**! 😊", "sources": [], "verification": {"score": 1.0}, "chunks": []}

    chunks = retrieve_chunks(query, top_k=top_k)
    _last_retrieved_chunks = chunks
    answer = generate_answer(query, chunks, lang, user_name, user_points)
    verification = Verifier().verify(answer, chunks)
    sources = [] if (answer.strip() == UNKNOWN_ANSWER_EN or answer.strip() == UNKNOWN_ANSWER_BN) else format_sources(chunks)
    return {"answer": answer, "sources": sources, "verification": verification, "chunks": chunks}


def latest_chunks() -> List[Dict]:
    return _last_retrieved_chunks
