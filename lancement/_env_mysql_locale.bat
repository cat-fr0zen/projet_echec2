@echo off
setlocal EnableExtensions EnableDelayedExpansion

set "PROJECT_ROOT=%~dp0.."
set "MYSQL_HOST=127.0.0.1"
set "MYSQL_PORT=3307"
set "MYSQL_DATABASE=projet_echec2"
set "MYSQL_USERNAME=root"
set "MYSQL_PASSWORD="
set "MYSQL_DATA_DIR=%PROJECT_ROOT%\runtime\mysql-data"
set "MYSQL_INI=%MYSQL_DATA_DIR%\my.ini"
set "MYSQL_BIN="
set "MYSQL_FLAVOR="

for /f "delims=" %%D in ('dir /ad /b /o-n "C:\laragon\bin\mysql\mariadb*" 2^>nul') do (
    if not defined MYSQL_BIN if exist "C:\laragon\bin\mysql\%%D\bin\mysqld.exe" (
        set "MYSQL_BIN=C:\laragon\bin\mysql\%%D\bin"
        set "MYSQL_FLAVOR=MariaDB (Laragon)"
    )
)

for /f "delims=" %%D in ('dir /ad /b /o-n "C:\laragon\bin\mysql\mysql*" 2^>nul') do (
    if not defined MYSQL_BIN if exist "C:\laragon\bin\mysql\%%D\bin\mysqld.exe" (
        set "MYSQL_BIN=C:\laragon\bin\mysql\%%D\bin"
        set "MYSQL_FLAVOR=MySQL (Laragon)"
    )
)

if not defined MYSQL_BIN if exist "C:\xampp\mysql\bin\mysqld.exe" (
    set "MYSQL_BIN=C:\xampp\mysql\bin"
    set "MYSQL_FLAVOR=MySQL/MariaDB (XAMPP)"
)

if not defined MYSQL_BIN (
    echo Aucun binaire MySQL ou MariaDB detecte.
    echo Installe Laragon ou XAMPP, puis relance le script.
    exit /b 1
)

if not exist "%MYSQL_DATA_DIR%" mkdir "%MYSQL_DATA_DIR%"

> "%MYSQL_INI%" (
    echo [mysqld]
    echo datadir=%MYSQL_DATA_DIR:\=/%
    echo port=%MYSQL_PORT%
    echo character-set-server=utf8mb4
    echo collation-server=utf8mb4_unicode_ci
    echo [client]
    echo port=%MYSQL_PORT%
)

endlocal & (
    set "PROJECT_ROOT=%PROJECT_ROOT%"
    set "MYSQL_HOST=%MYSQL_HOST%"
    set "MYSQL_PORT=%MYSQL_PORT%"
    set "MYSQL_DATABASE=%MYSQL_DATABASE%"
    set "MYSQL_USERNAME=%MYSQL_USERNAME%"
    set "MYSQL_PASSWORD=%MYSQL_PASSWORD%"
    set "MYSQL_DATA_DIR=%MYSQL_DATA_DIR%"
    set "MYSQL_INI=%MYSQL_INI%"
    set "MYSQL_BIN=%MYSQL_BIN%"
    set "MYSQL_FLAVOR=%MYSQL_FLAVOR%"
)

exit /b 0
