@echo off
setlocal EnableExtensions

rem Smart Fitness Platform - development starter
rem Khong chay migration, seed hoac key:generate tu dong.

set "FITNESS_ROOT=%~dp0"
if "%FITNESS_ROOT:~-1%"=="\" set "FITNESS_ROOT=%FITNESS_ROOT:~0,-1%"

set "FITNESS_PHP_EXE=%FITNESS_ROOT%\.tools\php\php.exe"
set "FITNESS_BE=%FITNESS_ROOT%\BE"
set "FITNESS_FE=%FITNESS_ROOT%\FE"
set "FITNESS_START_REALTIME=0"

echo.
echo ================================================
echo   SMART FITNESS PLATFORM - DEVELOPMENT START
echo ================================================
echo.

if not exist "%FITNESS_PHP_EXE%" (
    echo [ERROR] Khong tim thay PHP portable:
    echo         %FITNESS_PHP_EXE%
    echo         Kiem tra thu muc .tools hoac cai PHP 8.4+.
    pause
    exit /b 1
)

where node.exe >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Khong tim thay Node.js trong PATH.
    echo         Can Node.js 22.13+ hoac 24.3+.
    pause
    exit /b 1
)

where npm.cmd >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Khong tim thay npm trong PATH.
    pause
    exit /b 1
)

if not exist "%FITNESS_BE%\vendor" (
    echo [ERROR] Backend chua co dependency tai:
    echo         %FITNESS_BE%\vendor
    echo         Chay composer install trong thu muc BE truoc.
    pause
    exit /b 1
)

if not exist "%FITNESS_FE%\node_modules" (
    echo [ERROR] Frontend chua co dependency tai:
    echo         %FITNESS_FE%\node_modules
    echo         Chay npm ci trong thu muc FE truoc.
    pause
    exit /b 1
)

if not exist "%FITNESS_BE%\.env" (
    echo [ERROR] Chua co BE\.env.
    echo         Tao file nay tu BE\.env.example, sau do cau hinh Database va APP_KEY.
    pause
    exit /b 1
)

if not exist "%FITNESS_FE%\.env" if exist "%FITNESS_FE%\.env.example" (
    copy /Y "%FITNESS_FE%\.env.example" "%FITNESS_FE%\.env" >nul
    echo [INFO] Da tao FE\.env tu FE\.env.example.
)

if /I "%~1"=="--check" (
    echo [OK] Kiem tra cau hinh start.bat thanh cong.
    exit /b 0
)

echo [INFO] Dang mo Backend Laravel tai http://127.0.0.1:8000 ...
start "Smart Fitness - Backend" powershell.exe -NoExit -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%FITNESS_BE%'; & '%FITNESS_PHP_EXE%' artisan serve --host=127.0.0.1 --port=8000"

echo [INFO] Dang mo Frontend Vite tai http://127.0.0.1:5173 ...
start "Smart Fitness - Frontend" powershell.exe -NoExit -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%FITNESS_FE%'; npm.cmd run dev -- --host 127.0.0.1"

if /I "%FITNESS_START_REALTIME%"=="1" (
    echo [INFO] Dang mo Reverb va scheduler ...
    start "Smart Fitness - Reverb" powershell.exe -NoExit -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%FITNESS_BE%'; & '%FITNESS_PHP_EXE%' artisan reverb:start"
    start "Smart Fitness - Scheduler" powershell.exe -NoExit -ExecutionPolicy Bypass -Command "Set-Location -LiteralPath '%FITNESS_BE%'; & '%FITNESS_PHP_EXE%' artisan schedule:work"
)

echo.
echo [OK] Da khoi dong cac tien trinh chinh.
echo      Frontend: http://127.0.0.1:5173
echo      Backend:  http://127.0.0.1:8000
if /I "%FITNESS_START_REALTIME%"=="0" echo      Realtime: dang tat; doi FITNESS_START_REALTIME=1 neu can.
echo.
echo Dong cua so nay khong dung cac tien trinh da mo.
echo.
exit /b 0
