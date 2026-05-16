# Start the Notun Alo RAG service locally
# Run this in a terminal window (keeps running until Ctrl+C)

$ErrorActionPreference = "Stop"
$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectDir

Write-Host "Starting Notun Alo RAG service..." -ForegroundColor Green
Write-Host "Embedding model loading may take 20-40 seconds on first start." -ForegroundColor Yellow
Write-Host ""

$env:HF_HUB_OFFLINE = "1"
$env:TRANSFORMERS_OFFLINE = "1"

& ".venv\Scripts\python.exe" app.py
