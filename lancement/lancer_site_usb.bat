@echo off
setlocal EnableExtensions

REM ============================================================
REM Lanceur local du site Projet_echec2 avec Oracle 19c / XAMPP.
REM
REM Ce script:
REM - retrouve le projet;
REM - privilegie le PHP de XAMPP si disponible;
REM - cree .env depuis .env.example au premier lancement;
REM - verifie oci8_19 et la connexion Oracle avant d'ouvrir le site;
REM - lance le serveur PHP local securise par routeur.php.
REM ============================================================

set "SCRIPT_DIR=%~dp0"
set "PROJECT_DIR=%SCRIPT_DIR%.."
set "XAMPP_DIR=C:\xampp"
set "PHP_EXE="

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
    echo Exemple: C:\DEV\vscode_workspace\Projet_echec2
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

set "ENV_FILE=%PROJECT_DIR%\.env"
set "ENV_EXAMPLE_FILE=%PROJECT_DIR%\.env.example"

if not exist "%ENV_FILE%" (
    if exist "%ENV_EXAMPLE_FILE%" (
        copy "%ENV_EXAMPLE_FILE%" "%ENV_FILE%" >nul
        echo.
        echo [ACTION NECESSAIRE] Le fichier .env vient d'etre cree.
        echo Ouvre "%ENV_FILE%" puis renseigne au minimum:
        echo - ORACLE_SERVICE
        echo - ORACLE_USER
        echo - ORACLE_PASSWORD
        echo - NEWSLETTER_CONSENT_SALT
        echo.
        echo Relance ensuite ce fichier .bat.
        pause
        exit /b 1
    )

    echo [ERREUR] Aucun fichier .env ni .env.example trouve dans "%PROJECT_DIR%".
    pause
    exit /b 1
)

if exist "%XAMPP_DIR%\php\php.exe" (
    set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
)

if not defined PHP_EXE (
    for /f "delims=" %%P in ('where php 2^>nul') do (
        if not defined PHP_EXE set "PHP_EXE=%%P"
    )
)

if not defined PHP_EXE (
    echo [ERREUR] PHP n'est pas detecte.
    echo Installe XAMPP ou ajoute PHP au PATH, puis relance ce fichier.
    pause
    exit /b 1
)

echo.
echo ===========================================
echo  Projet Echec 2 - Verification Oracle
echo ===========================================
echo  Projet : %PROJECT_DIR%
echo  PHP    : %PHP_EXE%
echo  Env    : %ENV_FILE%
echo ===========================================
echo.

"%PHP_EXE%" "%PROJECT_DIR%\lancement\verifier_connexion_oracle.php" "%ENV_FILE%"
if errorlevel 1 (
    echo.
    echo [ERREUR] Le site ne peut pas demarrer tant que la base Oracle n'est pas joignable.
    echo Corrige la configuration ci-dessus, puis relance ce .bat.
    pause
    exit /b 1
)

if not exist "%PROJECT_DIR%\journaux" mkdir "%PROJECT_DIR%\journaux"
if not exist "%PROJECT_DIR%\stockage_runtime" mkdir "%PROJECT_DIR%\stockage_runtime"
if not exist "%PROJECT_DIR%\stockage_runtime\sessions" mkdir "%PROJECT_DIR%\stockage_runtime\sessions"
if not exist "%PROJECT_DIR%\ressources\media\uploads" mkdir "%PROJECT_DIR%\ressources\media\uploads"

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
echo  Projet Echec 2 - Site pret
echo ===========================================
echo  Dossier : %PROJECT_DIR%
echo  URL     : %URL%
echo ===========================================
echo.
echo Laisse cette fenetre ouverte pour garder le site actif.
echo Appuie sur CTRL+C pour arreter le serveur.
echo.

start "" "%URL%"
"%PHP_EXE%" -S %HOST%:%PORT% -t "%PROJECT_DIR%" "%PROJECT_DIR%\routeur.php"

endlocal
