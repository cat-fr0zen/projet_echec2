@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM ============================================================
REM Lanceur portable du site Projet_echec2
REM Objectif: pouvoir lancer le site depuis n'importe quel dossier
REM (y compris une cle USB), sans chemin absolu.
REM ============================================================

set "SCRIPT_DIR=%~dp0"
set "PROJECT_DIR=%SCRIPT_DIR%.."

REM Si le .bat n'est plus dans le projet, on demande le chemin du projet.
if not exist "%PROJECT_DIR%\index.php" (
    set "PROJECT_DIR="
)
if not exist "%PROJECT_DIR%\routeur.php" (
    set "PROJECT_DIR="
)

if not defined PROJECT_DIR (
    echo.
    echo Ce lanceur peut fonctionner seul.
    echo Indique le dossier du projet contenant index.php et routeur.php.
    echo Exemple: E:\Projet_echec2
    echo.
    set /p "PROJECT_DIR=Chemin du projet: "
)

if not exist "%PROJECT_DIR%\index.php" (
    echo [ERREUR] index.php introuvable dans "%PROJECT_DIR%".
    pause
    exit /b 1
)

if not exist "%PROJECT_DIR%\routeur.php" (
    echo [ERREUR] routeur.php introuvable dans "%PROJECT_DIR%".
    pause
    exit /b 1
)

where php >nul 2>nul
if errorlevel 1 (
    echo [ERREUR] PHP n'est pas detecte dans le PATH.
    echo Installe PHP puis relance ce fichier.
    pause
    exit /b 1
)

if not exist "%PROJECT_DIR%\journaux" mkdir "%PROJECT_DIR%\journaux"
if not exist "%PROJECT_DIR%\donnees" mkdir "%PROJECT_DIR%\donnees"
if not exist "%PROJECT_DIR%\donnees\cache" mkdir "%PROJECT_DIR%\donnees\cache"
if not exist "%PROJECT_DIR%\donnees\sessions" mkdir "%PROJECT_DIR%\donnees\sessions"

set "HOST=127.0.0.1"
set "PORT=8000"

:FIND_PORT
powershell -NoProfile -Command "try { $c = New-Object System.Net.Sockets.TcpClient('%HOST%', %PORT%); $c.Close(); exit 1 } catch { exit 0 }"
if errorlevel 1 (
    set /a PORT+=1
    if %PORT% GTR 8100 (
        echo [ERREUR] Aucun port disponible entre 8000 et 8100.
        pause
        exit /b 1
    )
    goto :FIND_PORT
)

set "URL=http://%HOST%:%PORT%/"

echo.
echo ===========================================
echo  Projet Echec 2 - Demarrage du serveur
echo ===========================================
echo  Dossier : %PROJECT_DIR%
echo  URL     : %URL%
echo ===========================================
echo.
echo Laisse cette fenetre ouverte pour garder le site actif.
echo Appuie sur CTRL+C pour arreter le serveur.
echo.

start "" "%URL%"
php -S %HOST%:%PORT% -t "%PROJECT_DIR%" "%PROJECT_DIR%\routeur.php"

endlocal
