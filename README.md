# Projet Echec 2

Site Laravel du club d'echecs "Les Cavaliers d'Herouville".

Le but du projet est de proposer un site clair pour le club avec :
- des pages publiques ;
- un espace membre ;
- des cours en PDF ;
- une mediatheque ;
- des articles ;
- une mini-boutique ;
- une administration simple.

## Structure du projet

- `app/` : logique metier du site
- `app/Http/Controllers/` : points d'entree des requetes web
- `app/Repositories/` : acces a la base de donnees
- `app/Services/` : services externes ou traitements specialises
- `app/Support/` : outils internes qui relient les pages, les formulaires et les contenus
- `config/` : configuration Laravel
- `database/migrations/` : structure de la base versionnee
- `database/seeders/` : donnees de depart
- `database/sql/` : script SQL manuel de secours
- `lancement/` : script unique de demarrage local
- `public/` : fichiers accessibles depuis le navigateur
- `resources/views/` : vues Blade du site
- `routes/` : declaration des routes
- `runtime/` : donnees locales de travail, non prevues pour la production
- `storage/` : logs, cache et fichiers temporaires Laravel
- `tests/` : tests automatiques

## Fichiers principaux

- [routes/web.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/routes/web.php)
Role : declare les pages, les formulaires et les telechargements proteges.

- [app/Http/Controllers/PageController.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Http/Controllers/PageController.php)
Role : ouvre une page du site et delegue le rendu.

- [app/Http/Controllers/ActionController.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Http/Controllers/ActionController.php)
Role : recoit les formulaires du site.

- [app/Support/SitePageRenderer.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Support/SitePageRenderer.php)
Role : rassemble les donnees a afficher et charge la bonne vue.

- [app/Support/SiteActionHandler.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Support/SiteActionHandler.php)
Role : traite les actions POST comme connexion, inscription, gestion admin, cours ou boutique.

- [app/Support/SiteContent.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Support/SiteContent.php)
Role : contient les textes statiques, la navigation et la definition des pages.

- [app/Repositories/UserRepository.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Repositories/UserRepository.php)
Role : gere les comptes membres.

- [app/Repositories/CoursDocumentRepository.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Repositories/CoursDocumentRepository.php)
Role : gere les PDF de cours.

- [app/Repositories/BoutiqueProduitRepository.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/app/Repositories/BoutiqueProduitRepository.php)
Role : gere les produits de la boutique.

- [resources/views/layouts/app.blade.php](/abs/c:/DEV/vscode_workspace/Projet_echec2/resources/views/layouts/app.blade.php)
Role : squelette HTML principal commun aux pages.

## Organisation des vues

Les vues de cours ont ete renommes pour etre plus compréhensibles :
- `cours.blade.php` : page d'accueil des cours
- `cours_livrets.blade.php` : entree des livrets
- `cours_livret.blade.php` : page reutilisee pour un livret precis
- `cours_progression.blade.php` : entree Methodologie / Strategie
- `cours_rubrique.blade.php` : page generique pour une rubrique simple

Le slug de route historique `guide` est conserve pour ne pas casser l'existant, mais il correspond bien a la rubrique "Cours".

## Comment lancer le projet

Le seul script a utiliser est :

- [demarrer_tout_le_site.bat](/abs/c:/DEV/vscode_workspace/Projet_echec2/lancement/demarrer_tout_le_site.bat)

Il verifie PHP, MySQL, Composer, la base de donnees, lance les migrations puis demarre le site sur `http://127.0.0.1:8000`.

## Technologies utilisees

- PHP 8.2+
- Laravel 11
- MySQL ou MariaDB
- Blade pour les vues
- PHPUnit pour les tests

## Fonctionnement general

1. Le navigateur appelle une route dans `routes/web.php`.
2. Le controleur correspondant recoit la demande.
3. Si c'est une page, `PageController` transmet a `SitePageRenderer`.
4. Si c'est un formulaire, `ActionController` transmet a `SiteActionHandler`.
5. Les repositories lisent ou ecrivent dans la base.
6. Les vues Blade affichent le resultat final.

## Base de donnees

- moteur attendu : MySQL ou MariaDB
- script SQL manuel disponible : [create_database_mysql_mariadb.sql](/abs/c:/DEV/vscode_workspace/Projet_echec2/database/sql/create_database_mysql_mariadb.sql)
- structure versionnee : `database/migrations/`
- donnees de depart : `database/seeders/`

## Configuration utile

- modele production : [`.env.example`](/abs/c:/DEV/vscode_workspace/Projet_echec2/.env.example)
- modele local : [`.env.local.example`](/abs/c:/DEV/vscode_workspace/Projet_echec2/.env.local.example)

Pour Gmail en SMTP :
- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.gmail.com`
- `MAIL_PORT=587`
- `MAIL_ENCRYPTION=tls`
- `MAIL_USERNAME=...`
- `MAIL_PASSWORD=...`
- `MAIL_FROM_ADDRESS=...`
- `MAIL_FROM_NAME="Cavaliers d'Herouville"`

## Stabilite et publication

- la structure Laravel de base est conservee pour ne pas fragiliser le projet ;
- les noms des fichiers centraux ont ete clarifies ;
- les fichiers sensibles, caches, logs et donnees locales restent ignores par Git ;
- `runtime/` sert au travail local et ne doit pas etre publie tel quel.

Avant une mise en production :
1. preparer un vrai `.env` serveur
2. activer HTTPS
3. configurer le SMTP reel
4. lancer `php artisan migrate --force`
5. vider les caches avec `php artisan optimize:clear`
6. verifier les droits d'ecriture sur `storage/` et `bootstrap/cache/`

## Credits

- Mattheo Mullois
- Association Les Cavaliers d'Herouville
