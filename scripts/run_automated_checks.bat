@echo off
setlocal
cd /d "%~dp0.."
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0run_automated_checks.ps1"
exit /b %ERRORLEVEL%
