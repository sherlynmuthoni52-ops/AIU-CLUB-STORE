@echo off
title AIU Club Store - Go Live

echo ============================================
echo   AIU Club Store - Development Server
echo ============================================
echo.

:: Validate we are in the project root
if not exist "%~dp0index.php" (
    echo ERROR: index.php not found in the current directory.
    echo Make sure this script is placed in the project root folder.
    pause
    exit /b 1
)

:: Check for XAMPP PHP
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: XAMPP PHP not found at C:\xampp\php\php.exe
    echo Please install XAMPP or update the path in this script.
    pause
    exit /b 1
)

echo Starting PHP built-in server on http://localhost:8000/
echo Database: MySQL must be running on port 3306 (XAMPP)
echo.
echo A separate window titled "AIU Club Store Server" will open.
echo Close that window to stop the server.
echo.

:: Navigate to project directory and start server in a new window
cd /d "%~dp0"
start "AIU Club Store Server" cmd /c ""C:\xampp\php\php.exe" -S localhost:8000"

:: Wait briefly for the server to bind, then open the browser
timeout /t 2 /nobreak >nul
start http://localhost:8000/index.php
