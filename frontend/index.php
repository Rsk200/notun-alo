<?php
// Optional standalone RAG demo page. The main app integration is chatbot.php.
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notun Alo RAG Demo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Noto Sans Bengali', system-ui, sans-serif; background: #f5f7f4; color: #17211a; }
        main { max-width: 880px; margin: 0 auto; padding: 32px 18px; }
        h1 { margin: 0 0 16px; font-size: 2rem; }
        section { background: #fff; border: 1px solid #dce5dd; border-radius: 8px; padding: 18px; margin-bottom: 16px; }
        textarea { width: 100%; min-height: 110px; padding: 12px; border: 1px solid #cbd8ce; border-radius: 6px; font: inherit; box-sizing: border-box; }
        button { border: 0; border-radius: 6px; padding: 10px 16px; background: #247a43; color: #fff; font-weight: 700; cursor: pointer; }
        button:disabled { opacity: .6; cursor: wait; }
        .answer { white-space: pre-wrap; line-height: 1.7; }
        .sources a { display: inline-block; margin: 6px 8px 0 0; color: #247a43; font-weight: 700; }
        .muted { color: #66756b; }
    </style>
</head>
<body>
<main>
    <h1>Notun Alo RAG Demo</h1>
    <section>
        <form id="uploadForm">
            <label>Upload document</label><br><br>
            <input type="file" name="file" accept=".pdf,.docx,.txt,.csv,.xlsx" required>
            <button type="submit">Upload and ingest</button>
            <p id="uploadStatus" class="muted"></p>
        </form>
    </section>
    <section>
        <textarea id="query" placeholder="রিসাইক্লিং সম্পর্কে প্রশ্ন করুন..."></textarea><br><br>
        <button id="askBtn">Ask</button>
    </section>
    <section>
        <div id="answer" class="answer muted">Answer will appear here.</div>
        <div id="sources" class="sources"></div>
    </section>
</main>
<script>
const API = '<?= rtrim((string)getenv('RAG_API_URL') ?: 'http://localhost:5000', '/') ?>';
const answer = document.getElementById('answer');
const sources = document.getElementById('sources');

document.getElementById('uploadForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = document.getElementById('uploadStatus');
    status.textContent = 'Uploading...';
    const res = await fetch(API + '/upload', { method: 'POST', body: new FormData(event.target) });
    const data = await res.json();
    status.textContent = res.ok ? 'Uploaded and indexed: ' + data.file : (data.error || 'Upload failed');
});

document.getElementById('askBtn').addEventListener('click', async () => {
    const query = document.getElementById('query').value.trim();
    if (!query) return;
    answer.textContent = 'Thinking...';
    sources.innerHTML = '';
    const res = await fetch(API + '/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query, language: /[\u0980-\u09FF]/.test(query) ? 'bn' : 'en' })
    });
    const data = await res.json();
    answer.textContent = data.answer || data.error || 'No answer.';
    (data.sources || []).forEach((source) => {
        const a = document.createElement('a');
        a.href = '#';
        a.textContent = source.filename + (source.page_number ? ' p.' + source.page_number : '');
        a.title = source.source_doc || '';
        sources.appendChild(a);
    });
});
</script>
</body>
</html>
