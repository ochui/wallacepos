@echo off
REM WallacePOS Development Server Launcher (Windows)
REM Usage: devserver.bat [port] [host]

REM Default values
set PORT=%1
set HOST=%2
if "%PORT%"=="" set PORT=8080
if "%HOST%"=="" set HOST=localhost

REM Get the directory where this script is located
set DIR=%~dp0

REM Check if PHP is installed
php --version >nul 2>&1
if errorlevel 1 (
    echo Error: PHP is not installed or not in PATH
    pause
    exit /b 1
)

REM Check if devserver.php exists
if not exist "%DIR%devserver.php" (
    echo Error: devserver.php not found in %DIR%
    pause
    exit /b 1
)

REM Start the development server
echo Starting WallacePOS Development Server...
php "%DIR%devserver.php" %PORT% %HOST%
pause
