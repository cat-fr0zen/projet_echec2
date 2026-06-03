@echo off
setlocal

call "%~dp0_env_mysql_locale.bat" || exit /b 1

if exist "%MYSQL_DATA_DIR%\mysql" (
    echo Dossier MySQL ou MariaDB deja initialise :
    echo %MYSQL_DATA_DIR%
    exit /b 0
)

echo Initialisation d'un dossier MySQL ou MariaDB local...
echo Source binaire detectee : %MYSQL_FLAVOR%

if exist "%MYSQL_BIN%\mariadb-install-db.exe" (
    "%MYSQL_BIN%\mariadb-install-db.exe" --datadir="%MYSQL_DATA_DIR%" --auth-root-authentication-method=normal
) else if exist "%MYSQL_BIN%\mysql_install_db.exe" (
    "%MYSQL_BIN%\mysql_install_db.exe" --datadir="%MYSQL_DATA_DIR%"
) else (
    "%MYSQL_BIN%\mysqld.exe" --defaults-file="%MYSQL_INI%" --initialize-insecure --console
)

if errorlevel 1 (
    echo.
    echo Echec de l'initialisation du dossier MySQL ou MariaDB local.
    exit /b 1
)

echo Initialisation terminee.
endlocal
