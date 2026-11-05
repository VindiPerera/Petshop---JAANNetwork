@echo off
rem Starts the project's own MySQL (port 3307) and the Laravel dev server.
cd /d "%~dp0"

tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo Starting MySQL on port 3307...
    start "PetShop MySQL" /min C:\xampp\mysql\bin\mysqld.exe --no-defaults --datadir="%~dp0.mysql-data" --port=3307 --bind-address=127.0.0.1 --console
    timeout /t 8 /nobreak >nul
)

echo Open http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000
