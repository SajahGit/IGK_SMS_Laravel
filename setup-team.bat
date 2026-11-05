@echo off
setlocal
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 (
    echo PHP was not found. Install PHP 8.2 or newer and add it to PATH.
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo Composer was not found. Install Composer 2 and add it to PATH.
    exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
    echo npm was not found. Install Node.js 20.19 or newer and add it to PATH.
    exit /b 1
)

set "NEW_ENV="
if not exist ".env" (
    if not exist ".env.example" (
        echo .env.example is missing.
        exit /b 1
    )
    copy ".env.example" ".env" >nul
    if errorlevel 1 exit /b 1
    set "NEW_ENV=1"
)

call composer install --no-interaction
if errorlevel 1 exit /b 1

call npm ci
if errorlevel 1 exit /b 1

if defined NEW_ENV (
    call php artisan key:generate
    if errorlevel 1 exit /b 1
)

call npm run build
if errorlevel 1 exit /b 1

echo.
echo Dependencies and frontend assets are ready.
echo Edit .env to use your own local database, then run:
echo   php artisan migrate --seed
echo   php artisan storage:link
echo   php artisan serve
echo Read README-SETUP.md before running migrations.
exit /b 0
