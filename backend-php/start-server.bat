@echo off
REM =============================================================
REM  start-server.bat — Launch AISU PHP Backend on Windows
REM  Run this from the backend-php directory
REM =============================================================

echo.
echo  ============================================
echo   AISU Backend Server (PHP)
echo   Starting on http://localhost:8000
echo  ============================================
echo.

cd /d "%~dp0"

REM Create data directory if not exists
if not exist "data" mkdir data

REM Start PHP built-in development server
php -S localhost:8000 index.php

pause
