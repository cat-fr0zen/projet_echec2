# Laravel Port

Ce dossier contient le port Laravel/MySQL du projet historique `Projet_echec2`.

## Etat

- runtime cible: Laravel 11 + PHP 8.2 + MySQL/MariaDB
- base cible: `projet_ecnec2`
- nouveau runtime sans dependance Oracle
- ancien code conserve a la racine du projet comme sauvegarde de migration

## Blocage local actuel

Le telechargement du framework Laravel via Composer est bloque sur cette machine par un probleme SSL/certificats Windows/Composer.

Le port applicatif a donc ete prepare ici, mais l'installation complete des dependances `vendor/` devra etre relancee des que Composer pourra joindre Packagist correctement.

## Commandes prevues une fois Composer retabli

```powershell
cd C:\DEV\vscode_workspace\Projet_echec2\laravel_app
C:\laragon\bin\composer\composer.bat install
C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS projet_ecnec2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan serve
```

## Lancement Laragon

- place le dossier `laravel_app` dans `C:\laragon\www` ou cree un virtual host pointant vers `laravel_app\public`
- demarre Apache et MySQL/MariaDB dans Laragon
- ouvre ensuite `http://projet_ecnec2.test` si le virtual host existe, sinon `http://127.0.0.1:8000` avec `php artisan serve`
