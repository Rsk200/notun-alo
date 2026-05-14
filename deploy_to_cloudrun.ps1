param(
    [string]$ProjectId = "notun-alo-project",
    [string]$Region = "us-central1",
    [string]$ServiceName = "notun-alo-rag",
    [string]$GEMINI_API_KEY = ""
)

if ([string]::IsNullOrEmpty($GEMINI_API_KEY)) {
    Write-Host "ERROR: GEMINI_API_KEY is required. Get one at https://aistudio.google.com"
    exit 1
}

# Enable required APIs
Write-Host "Enabling Cloud Build and Cloud Run APIs..."
gcloud services enable cloudbuild.googleapis.com run.googleapis.com --project $ProjectId

# Submit build
Write-Host "Building and pushing Docker image..."
gcloud builds submit --config cloudbuild.yaml --project $ProjectId

# Deploy to Cloud Run with 2GB RAM (enough for ML models)
Write-Host "Deploying to Cloud Run..."
gcloud run deploy $ServiceName `
    --image gcr.io/$ProjectId/notun-alo-rag `
    --region $Region `
    --platform managed `
    --allow-unauthenticated `
    --memory 2Gi `
    --cpu 2 `
    --set-env-vars "GEMINI_API_KEY=$GEMINI_API_KEY,FLASK_APP=app.py" `
    --min-instances 0 `
    --max-instances 2 `
    --no-cpu-throttling `
    --project $ProjectId

Write-Host ""
Write-Host "Deployment complete!"
Write-Host "Run this to get the URL: gcloud run services describe $ServiceName --region $Region --format 'value(status.url)' --project $ProjectId"
