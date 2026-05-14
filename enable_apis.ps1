# Enable required Google Cloud APIs for Cloud Run deployment
# This script assumes gcloud is already installed and in PATH.
param(
    [string]$ProjectId = "notun-alo-project"
)

# Set the project
gcloud config set project $ProjectId

# Enable Cloud Build and Cloud Run APIs
gcloud services enable cloudbuild.googleapis.com run.googleapis.com --project $ProjectId

Write-Host "Enabled Cloud Build and Cloud Run APIs for project $ProjectId"
