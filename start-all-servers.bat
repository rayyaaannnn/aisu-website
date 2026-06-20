@echo off
REM ============================================================
REM  AISU Website — Start All Servers
REM  1. PHP Backend (port 8000)
REM  2. Quiz WebSocket Server (port 3001)
REM  3. Frontend HTTP Server (port 3000)
REM ============================================================

echo.
echo   ============================================
echo    AISU Website Server Launcher
echo   ============================================
echo.

REM Start PHP Backend (with router — serves static files + API)
echo [1/3] Starting PHP Backend on port 8000...
REM Set Razorpay key from environment or use default
if "%RAZORPAY_KEY_SECRET%"=="" set RAZORPAY_KEY_SECRET=3JokfG5RogFcbZV2k3PEWnr0
start "AISU PHP Backend" /min cmd /c "cd /d %~dp0 && set RAZORPAY_KEY_SECRET=%RAZORPAY_KEY_SECRET% && php -S localhost:8000 router.php"

REM Start Quiz WebSocket Server
echo [2/3] Starting Quiz WebSocket Server on port 3001...
start "AISU Quiz Server" /min cmd /c "cd /d %~dp0quiz-server && node quiz-server.js"

REM Start Frontend Server
echo [3/3] Starting Frontend HTTP Server on port 3000...
start "AISU Frontend" /min cmd /c "cd /d %~dp0 && python -m http.server 3000"

echo.
echo   All servers started!
echo   -------------------------------------------
echo   Frontend:  http://localhost:3000
echo   Backend:   http://localhost:8000
echo   Quiz WS:   http://localhost:3001
echo   -------------------------------------------
echo   Open http://localhost:3000 in your browser
echo.
echo   Press any key to stop all servers...
pause >nul

REM Kill servers
taskkill /fi "WINDOWTITLE eq AISU PHP Backend" /f >nul 2>&1
taskkill /fi "WINDOWTITLE eq AISU Quiz Server" /f >nul 2>&1
taskkill /fi "WINDOWTITLE eq AISU Frontend" /f >nul 2>&1
echo   All servers stopped.
