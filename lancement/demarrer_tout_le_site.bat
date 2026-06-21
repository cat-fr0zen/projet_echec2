:: Fichier du projet. Role : participer au fonctionnement du site. Theme principal : demarrer tout le site.

@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0\.."
set "ROOT_DIR=%CD%"

echo.
echo ==========================================
echo   Cavaliers d'Herouville - Demarrage total
echo ==========================================
echo.

set "LARAGON_DIR=C:\laragon"
set "PHP_EXE="
set "MYSQL_EXE="
set "MYSQLD_EXE="
set "MYSQL_BASE_DIR="
set "MYSQL_DATA_DIR="
set "DB_HOST=127.0.0.1"
set "DB_PORT=3307"
set "DB_NAME=projet_echec2"
set "DB_USER=projet_echec2_app"
set "DB_PASSWORD=73f885ab885a37c18a4d7a24350be956"

if exist ".env" (
    for /f "usebackq tokens=1,* delims==" %%A in (".env") do (
        if /I "%%A"=="DB_HOST" set "DB_HOST=%%B"
        if /I "%%A"=="DB_PORT" set "DB_PORT=%%B"
        if /I "%%A"=="DB_DATABASE" set "DB_NAME=%%B"
        if /I "%%A"=="DB_USERNAME" set "DB_USER=%%B"
        if /I "%%A"=="DB_PASSWORD" set "DB_PASSWORD=%%B"
    )
)

call :trim_quotes DB_HOST
call :trim_quotes DB_PORT
call :trim_quotes DB_NAME
call :trim_quotes DB_USER
call :trim_quotes DB_PASSWORD

call :find_php
if not defined PHP_EXE (
    echo [ERREUR] PHP introuvable. Installe PHP ou Laragon.
    goto :fail
)

call :find_mysql
if not defined MYSQL_EXE (
    echo [ERREUR] Client MySQL introuvable. Verifie Laragon.
    goto :fail
)

if not defined MYSQLD_EXE (
    echo [ERREUR] Serveur mysqld introuvable. Verifie Laragon.
    goto :fail
)

if not defined MYSQL_BASE_DIR (
    echo [ERREUR] Base dir MySQL introuvable. Verifie Laragon.
    goto :fail
)

if not defined MYSQL_DATA_DIR (
    echo [ERREUR] Dossier de donnees MySQL introuvable.
    goto :fail
)

echo [INFO] Racine projet : %ROOT_DIR%
echo [INFO] PHP          : %PHP_EXE%
echo [INFO] MySQL client : %MYSQL_EXE%
echo [INFO] MySQL daemon : %MYSQLD_EXE%
echo [INFO] MySQL base   : %MYSQL_BASE_DIR%
echo [INFO] Base locale  : %DB_HOST%:%DB_PORT% / %DB_NAME%
echo.

call :ensure_vendor
if errorlevel 1 goto :fail

call :ensure_app_key
if errorlevel 1 goto :fail

call :ensure_mysql_running
if errorlevel 1 goto :fail

call :ensure_database_exists
if errorlevel 1 goto :fail

echo.
echo [ETAPE] Migration et seeding...
"%PHP_EXE%" artisan migrate --seed --force
if errorlevel 1 (
    echo [ERREUR] Echec pendant migrate --seed.
    goto :fail
)

echo.
echo [OK] Tout est pret.
echo [INFO] Le site va se lancer sur http://127.0.0.1:8000
echo [INFO] Ferme cette fenetre avec Ctrl+C pour arreter le serveur Laravel.
echo.
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000
goto :eof

:ensure_vendor
if exist "vendor\autoload.php" (
    echo [OK] Dependances Composer deja presentes.
    exit /b 0
)

echo [ETAPE] Installation des dependances Composer...
where composer >nul 2>nul
if errorlevel 1 (
    echo [ERREUR] Composer introuvable dans le PATH.
    echo         Ouvre Laragon une fois ou installe Composer.
    exit /b 1
)

composer install
if errorlevel 1 (
    echo [ERREUR] composer install a echoue.
    exit /b 1
)

exit /b 0

:ensure_app_key
findstr /B /C:"APP_KEY=" ".env" >nul 2>nul
if errorlevel 1 (
    echo [ETAPE] Generation de APP_KEY...
    "%PHP_EXE%" artisan key:generate --force
    if errorlevel 1 (
        echo [ERREUR] Impossible de generer APP_KEY.
        exit /b 1
    )
    exit /b 0
)

for /f "tokens=1,* delims==" %%A in ('findstr /B /C:"APP_KEY=" ".env"') do set "APP_KEY_VALUE=%%B"
call :trim_quotes APP_KEY_VALUE
if not defined APP_KEY_VALUE (
    echo [ETAPE] Generation de APP_KEY...
    "%PHP_EXE%" artisan key:generate --force
    if errorlevel 1 (
        echo [ERREUR] Impossible de generer APP_KEY.
        exit /b 1
    )
)

exit /b 0

:ensure_mysql_running
echo [ETAPE] Verification de MySQL sur le port %DB_PORT%...
"%MYSQL_EXE%" --protocol=tcp --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% --password=%DB_PASSWORD% --execute="SELECT 1" >nul 2>nul
if not errorlevel 1 (
    echo [OK] MySQL repond deja.
    exit /b 0
)

echo [INFO] MySQL ne repond pas encore. Tentative de demarrage...
set "MYSQL_START_LOG=%ROOT_DIR%\storage\logs\mysql-local-start.log"
if not exist "storage\logs" mkdir "storage\logs" >nul 2>nul

start "MySQL local Projet_echec2" /min powershell -NoProfile -ExecutionPolicy Bypass -Command ^
 "Start-Process -FilePath '%MYSQLD_EXE%' -ArgumentList @('--defaults-file=%MYSQL_BASE_DIR%\my.ini','--basedir=%MYSQL_BASE_DIR%','--datadir=%MYSQL_DATA_DIR%','--port=%DB_PORT%','--bind-address=127.0.0.1','--mysqlx=0','--console') -WindowStyle Hidden"

for /l %%I in (1,1,25) do (
    timeout /t 1 /nobreak >nul
    "%MYSQL_EXE%" --protocol=tcp --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% --password=%DB_PASSWORD% --execute="SELECT 1" >nul 2>nul
    if not errorlevel 1 (
        echo [OK] MySQL est demarre.
        exit /b 0
    )
)

echo [ERREUR] MySQL n'a pas pu demarrer automatiquement.
echo         Regarde le log : %MYSQL_START_LOG%
echo         Tu peux aussi ouvrir Laragon manuellement puis relancer ce script.
exit /b 1

:ensure_database_exists
echo [ETAPE] Verification de la base %DB_NAME%...
"%MYSQL_EXE%" --protocol=tcp --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% --password=%DB_PASSWORD% --database=%DB_NAME% --execute="SELECT 1" >nul 2>nul
if errorlevel 1 (
    call :repair_app_user
    "%MYSQL_EXE%" --protocol=tcp --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% --password=%DB_PASSWORD% --database=%DB_NAME% --execute="SELECT 1" >nul 2>nul
    if errorlevel 1 (
        echo [ERREUR] Impossible de creer ou verifier la base %DB_NAME%.
        echo         Verifie que root local est accessible ou que le compte %DB_USER% existe.
        exit /b 1
    )
)

echo [OK] Base verifiee.
exit /b 0

:find_php
for /d %%D in ("%LARAGON_DIR%\bin\php\php-*") do (
    if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
)

if defined PHP_EXE exit /b 0

for /f "delims=" %%P in ('where php 2^>nul') do (
    if not defined PHP_EXE set "PHP_EXE=%%P"
)

exit /b 0

:find_mysql
for /d %%D in ("%LARAGON_DIR%\bin\mysql\mysql-*") do (
    if exist "%%~fD\bin\mysql.exe" set "MYSQL_EXE=%%~fD\bin\mysql.exe"
    if exist "%%~fD\bin\mysqld.exe" set "MYSQLD_EXE=%%~fD\bin\mysqld.exe"
    if exist "%%~fD\bin\mysqld.exe" set "MYSQL_BASE_DIR=%%~fD"
)

for /d %%D in ("%LARAGON_DIR%\data\mysql*") do (
    if exist "%%~fD" set "MYSQL_DATA_DIR=%%~fD"
)

if not defined MYSQL_EXE (
    for /f "delims=" %%M in ('where mysql 2^>nul') do (
        if not defined MYSQL_EXE set "MYSQL_EXE=%%M"
    )
)

exit /b 0

:repair_app_user
"%MYSQL_EXE%" --protocol=tcp --host=%DB_HOST% --port=%DB_PORT% --user=root --execute="CREATE DATABASE IF NOT EXISTS \`%DB_NAME%\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '%DB_USER%'@'localhost' IDENTIFIED BY '%DB_PASSWORD%'; CREATE USER IF NOT EXISTS '%DB_USER%'@'127.0.0.1' IDENTIFIED BY '%DB_PASSWORD%'; ALTER USER '%DB_USER%'@'localhost' IDENTIFIED WITH caching_sha2_password BY '%DB_PASSWORD%'; ALTER USER '%DB_USER%'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY '%DB_PASSWORD%'; GRANT ALL PRIVILEGES ON \`%DB_NAME%\`.* TO '%DB_USER%'@'localhost'; GRANT ALL PRIVILEGES ON \`%DB_NAME%\`.* TO '%DB_USER%'@'127.0.0.1'; FLUSH PRIVILEGES;" >nul 2>nul
exit /b 0

:trim_quotes
set "%~1=!%~1:"=!"
exit /b 0

:fail
echo.
echo [ECHEC] Le demarrage complet a rencontre un probleme.
echo         Lis les messages ci-dessus, corrige si besoin, puis relance ce .bat.
echo.
pause
exit /b 1
