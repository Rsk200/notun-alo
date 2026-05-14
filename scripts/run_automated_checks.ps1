# Notun Alo — full-project automated checks (Windows / PowerShell)
# Usage (from repo root):
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\run_automated_checks.ps1
# Capture full log:
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\run_automated_checks.ps1 *> .\check_log.txt

$ErrorActionPreference = "Continue"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

function Invoke-CaptureProcess {
    param(
        [string]$FilePath,
        [string[]]$ArgumentList,
        [string]$WorkingDirectory
    )
    $outF = [System.IO.Path]::GetTempFileName()
    $errF = [System.IO.Path]::GetTempFileName()
    try {
        $p = Start-Process -FilePath $FilePath -ArgumentList $ArgumentList -WorkingDirectory $WorkingDirectory `
            -NoNewWindow -Wait -PassThru `
            -RedirectStandardOutput $outF -RedirectStandardError $errF
        if (Test-Path -LiteralPath $outF) {
            $s = Get-Content -LiteralPath $outF -Raw
            if ($null -ne $s -and $s.Length -gt 0) { Write-Output $s }
        }
        if (Test-Path -LiteralPath $errF) {
            $e = Get-Content -LiteralPath $errF -Raw
            if ($null -ne $e -and $e.Length -gt 0) { Write-Output $e }
        }
        return $p.ExitCode
    }
    finally {
        Remove-Item -LiteralPath $outF -ErrorAction SilentlyContinue
        Remove-Item -LiteralPath $errF -ErrorAction SilentlyContinue
    }
}

Write-Output "========== 1) PHP -l (all *.php, exclude .venv) =========="

$phpExe = "C:\xampp\php\php.exe"
$phpCmd = $null
if (Test-Path -LiteralPath $phpExe) {
    $phpCmd = $phpExe
}
else {
    $w = Get-Command php -ErrorAction SilentlyContinue
    if ($w) { $phpCmd = $w.Source }
}

if (-not $phpCmd) {
    Write-Output "MISSING: C:\xampp\php\php.exe not found and 'php' not on PATH."
}
else {
    Write-Output "Using: $phpCmd"
    if (-not (Test-Path -LiteralPath $Root)) {
        Write-Output "MISSING: Root path does not exist: $Root"
    }
    else {
        $files = @(Get-ChildItem -LiteralPath $Root -Filter "*.php" -Recurse -File -ErrorAction SilentlyContinue |
            Where-Object { $_.FullName -notmatch '\\\.venv\\' })
        Write-Output "Total .php files (excluding .venv in path): $($files.Count)"
        $failCount = 0
        foreach ($f in $files) {
            $exit = Invoke-CaptureProcess -FilePath $phpCmd -ArgumentList @("-l", $f.FullName) -WorkingDirectory $Root
            if ($exit -ne 0) {
                Write-Output "FAIL: $($f.FullName)"
                $failCount++
            }
        }
        Write-Output "--- PHP syntax summary: failures = $failCount ---"
    }
}

Write-Output ""
Write-Output "========== 2) pytest: $Root\tests =========="

$py = Join-Path $Root ".venv\Scripts\python.exe"
if (-not (Test-Path -LiteralPath $py)) {
    $w = Get-Command python -ErrorAction SilentlyContinue
    if ($w) {
        $py = $w.Source
        Write-Output "FALLBACK python: $py"
    }
    else {
        Write-Output "MISSING: $Root\.venv\Scripts\python.exe and 'python' not on PATH."
        $py = $null
    }
}
else {
    Write-Output "Using: $py"
}

if ($py -and (Test-Path -LiteralPath (Join-Path $Root "tests"))) {
    $code = Invoke-CaptureProcess -FilePath $py -ArgumentList @("-m", "pytest", "tests", "-q", "--tb=short") -WorkingDirectory $Root
    Write-Output "exit code: $code"
}
elseif ($py) {
    Write-Output "SKIP: no tests\ folder at repo root."
}

Write-Output ""
Write-Output "========== 3) pytest: $Root\ai-service\tests =========="

$ais = Join-Path $Root "ai-service"
$py2 = Join-Path $Root ".venv\Scripts\python.exe"
if (-not (Test-Path -LiteralPath $py2)) {
    $w2 = Get-Command python -ErrorAction SilentlyContinue
    if ($w2) { $py2 = $w2.Source }
    else { $py2 = $null }
}

if ($py2 -and (Test-Path -LiteralPath (Join-Path $ais "tests"))) {
    Write-Output "Using: $py2"
    $code2 = Invoke-CaptureProcess -FilePath $py2 -ArgumentList @("-m", "pytest", "tests", "-q", "--tb=short") -WorkingDirectory $ais
    Write-Output "exit code: $code2"
}
elseif (-not (Test-Path -LiteralPath $ais)) {
    Write-Output "MISSING: $ais"
}
else {
    Write-Output "MISSING: python interpreter for pytest."
}

Write-Output ""
Write-Output "========== Done $(Get-Date -Format o) =========="
