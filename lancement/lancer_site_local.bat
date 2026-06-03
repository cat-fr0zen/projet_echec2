@echo off
setlocal

call "%~dp0demarrer_mysql_locale.bat" || exit /b 1
cd /d "%~dp0.."

echo Lancement du site Laravel sur http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000

endlocal
