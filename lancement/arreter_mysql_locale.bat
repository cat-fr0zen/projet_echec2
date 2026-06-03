@echo off
setlocal

call "%~dp0_env_mysql_locale.bat" || exit /b 1

echo Arret de la base locale sur %MYSQL_HOST%:%MYSQL_PORT%...
"%MYSQL_BIN%\mysqladmin.exe" -h %MYSQL_HOST% -P %MYSQL_PORT% -u %MYSQL_USERNAME% shutdown

if errorlevel 1 (
    echo.
    echo La base locale n'a pas repondu au shutdown.
    exit /b 1
)

echo Base locale arretee.
endlocal
