@echo off
setlocal

call "%~dp0_env_mysql_locale.bat" || exit /b 1

if not exist "%MYSQL_DATA_DIR%\mysql" (
    call "%~dp0initialiser_mysql_locale.bat" || exit /b 1
)

echo Verification de %MYSQL_FLAVOR% sur %MYSQL_HOST%:%MYSQL_PORT%...
"%MYSQL_BIN%\mysql.exe" -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USERNAME% -e "SELECT 1;" >nul 2>nul

if errorlevel 1 (
    echo Demarrage de la base locale...
    start "Projet Echec2 MySQL Local" /min cmd /k ""%MYSQL_BIN%\mysqld.exe" --defaults-file="%MYSQL_INI%""
    ping 127.0.0.1 -n 6 >nul
)

"%MYSQL_BIN%\mysql.exe" -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USERNAME% -e "SELECT VERSION() AS version;"

if errorlevel 1 (
    echo.
    echo La base locale n'a pas repondu.
    echo Consulte le contenu de runtime\\mysql-data pour le diagnostic.
    exit /b 1
)

echo.
echo Base locale disponible sur %MYSQL_HOST%:%MYSQL_PORT%.
endlocal
