@echo off
setlocal

call "%~dp0demarrer_mysql_locale.bat" || exit /b 1
call "%~dp0_env_mysql_locale.bat" || exit /b 1

cd /d "%PROJECT_ROOT%"

echo Creation de la base %MYSQL_DATABASE% si necessaire...
"%MYSQL_BIN%\mysql.exe" -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USERNAME% -e "CREATE DATABASE IF NOT EXISTS %MYSQL_DATABASE% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if errorlevel 1 (
    echo.
    echo Impossible de creer ou joindre la base %MYSQL_DATABASE%.
    exit /b 1
)

echo Lancement des migrations et seeders...
php artisan migrate --seed

if errorlevel 1 (
    echo.
    echo Les migrations Laravel ont echoue.
    exit /b 1
)

echo.
echo Base locale %MYSQL_DATABASE% prete.
endlocal
