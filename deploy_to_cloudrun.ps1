param(
    [string]$ProjectId = "notun-alo-project",
    [string]$Region = "us-central1",
    [string]$ServiceName = "notun-alo-service",
    [hashtable]$EnvVars = @{
        "DB_HOST" = "your-db-host";
        "DB_NAME" = "your-db-name";
        "DB_USER" = "your-db-user";
        "DB_PASSWORD" = "your-db-pass";
        "AI_API_KEY" = "your-ai-api-key"
    }
)

# Submit Cloud Build to build the Docker image
Write-Host "Submitting Cloud Build..."
gcloud builds submit --config cloudbuild.yaml --project $ProjectId

# Deploy to Cloud Run
$envArgs = ($EnvVars.GetEnumerator() | ForEach-Object { "--set-env-vars=$($_.Key)=$($_.Value)" }) -join " "
Write-Host "Deploying to Cloud Run..."
gcloud run deploy $ServiceName `
    --image gcr.io/$ProjectId/notun-alo `
    --region $Region `
    --platform managed `
    --allow-unauthenticated `
    $envArgs `
    --project $ProjectId

Write-Host "Deployment complete. Use 'gcloud run services describe $ServiceName --region $Region' to get the URL."
