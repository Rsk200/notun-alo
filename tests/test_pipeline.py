from __future__ import annotations

import io
import os
from pathlib import Path

import pytest

test_chroma_dir = Path(__file__).resolve().parents[1] / "scratch" / "test_chroma"
test_chroma_dir.mkdir(parents=True, exist_ok=True)
os.environ.setdefault("RAG_CHROMA_DIR", str(test_chroma_dir))

from app import app


@pytest.fixture()
def client():
    app.config.update(TESTING=True)
    with app.test_client() as test_client:
        yield test_client


def test_health(client):
    response = client.get("/health")
    assert response.status_code == 200
    assert response.get_json()["status"] == "ok"


def test_upload_and_chat_with_text_document(client):
    payload = b"Recycling reduces landfill waste and saves natural resources. Notun Alo supports recycling education."
    response = client.post(
        "/upload",
        data={"file": (io.BytesIO(payload), "rag_test_note.txt")},
        content_type="multipart/form-data",
    )
    assert response.status_code == 200
    upload_json = response.get_json()
    assert upload_json["ingestion"]["indexed_files"] in {0, 1}

    chat_response = client.post("/chat", json={"query": "What does recycling reduce?", "language": "en"})
    assert chat_response.status_code == 200
    data = chat_response.get_json()
    assert "answer" in data
    assert data["sources"]
    assert any("rag_test_note.txt" == source["filename"] for source in data["sources"])
