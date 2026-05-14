# Install Google Cloud SDK silently on Windows
# This script downloads the installer, runs it in silent mode, and adds gcloud to PATH.
$installerUrl = "https://dl.google.com/dl/cloudsdk/channels/rapid/GoogleCloudSDKInstaller.exe"
$installerPath = "$env:TEMP\GoogleCloudSDKInstaller.exe"
Invoke-WebRequest -Uri $installerUrl -OutFile $installerPath
# Silent install (default location C:\Program Files\Google\Cloud SDK)
Start-Process -FilePath $installerPath -ArgumentList "/S" -Wait
# Refresh environment variables for the current session
$env:Path += ";C:\Program Files\Google\Cloud SDK\google-cloud-sdk\bin"
# Set the desired project (replace with your actual project ID if different)
$projectId = "notun-alo-project"
gcloud config set project $projectId
Write-Host "Google Cloud SDK installed and project set to $projectId"
