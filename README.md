# Projet Échec 2

Prototype MVC en PHP pour un site d'association d'échecs avec espace membre local, cookies, thème clair/sombre, médiathèque encadrée et publication modérée.

## Ce qui a été mis en place

- `index.php` comme front controller
- `routeur.php` comme routeur unique pour toutes les URL
- `controleurs/ControleurPages.php` pour le routage des pages
- `controleurs/ControleurActions.php` pour les actions `POST`
- `modeles/ModeleSite.php` pour les données éditoriales et juridiques du site
- `modeles/DepotUtilisateurs.php` et `modeles/DepotArticles.php` pour le stockage JSON local
- `base_de_donnees/oracle/` pour le schéma SQL Oracle et la maintenance PL/SQL
- `vues/` pour le layout, les partiels et les pages
- `ressources/styles/style.css` et `ressources/scripts/site.js` pour le design, le motion system et les interactions

## Structure

```text
Projet_echec2/
|-- .gitignore
|-- index.php
|-- routeur.php
|-- start-server.ps1
|-- README.md
|-- base_de_donnees/
|   `-- oracle/
|       |-- 001_schema.sql
|       |-- 002_reference-data.sql
|       |-- 003_maintenance.sql
|       `-- data-model.md
|-- controleurs/
|   |-- ControleurActions.php
|   `-- ControleurPages.php
|-- donnees/
|   |-- articles.json
|   `-- utilisateurs.json
|-- journaux/
|   |-- server-error.log
|   `-- server-output.log
|-- modeles/
|   |-- DepotArticles.php
|   |-- StockageJson.php
|   |-- ModeleSite.php
|   `-- DepotUtilisateurs.php
|-- ressources/
|   |-- scripts/
|   |   `-- site.js
|   `-- styles/
|       `-- style.css
`-- vues/
    |-- mise-en-page.php
    |-- partiels/
    |   |-- modale-authentification.php
    |   |-- consentement.php
    |   |-- pied-de-page.php
    |   `-- entete.php
    `-- pages/
        |-- activites.php
        |-- articles.php
        |-- club.php
        |-- contact.php
        |-- guide.php
        |-- accueil.php
        |-- mediatheque.php
        |-- boutique.php
        |-- introuvable.php
        |-- profil.php
        `-- parametres.php
```

## Routes disponibles

- `/`
- `/guide`
- `/mediatheque`
- `/articles`
- `/boutique`
- `/club`
- `/activites`
- `/contact`
- `/profil`
- `/parametres`

## Lancement local

- lancer seulement `./start-server.ps1` depuis le dossier du projet
- ouvrir `http://127.0.0.1:8000/`
- le routeur unique est `routeur.php`
- le point d'entrée unique de l'application est `index.php`
- les logs serveur sont écrits dans `journaux/`

## Direction design

- ambiance éditoriale / club historique
- palette ivoire, vert profond, laiton
- titres serif et corps très lisible
- animations légères et utiles, sans effet gadget

## Motion system

- `fade-up` à l'entrée des sections
- `float` très légère sur les badges du hero
- `hover-lift` sur les cartes et boutons
- popup membre, menu burger et switch de thème en JS natif
- prise en charge de `prefers-reduced-motion`

## Espace membre

- connexion et inscription dans une popup
- champs d'inscription : nom, prénom, date de naissance facultative, email, mot de passe, description
- profil éditable après connexion
- articles créés par les membres avec statut `en_attente_validation`
- stockage local JSON pour le prototype

## Cookies et juridique

- consentement obligatoire à l'entrée du site
- cookie de thème `site_theme`
- cookie de consentement `site_consent`
- cookie de session PHP pour les membres connectés
- footer légal avec mentions légales, confidentialité, droit à l'image et conditions d'utilisation

## Base Oracle cible

- schéma pensé au plus près de Boyce-Codd
- prise en charge des comptes, profils, consentements, articles, médias, boutique et commandes
- gestion des images et vidéos via métadonnées Oracle, droits de diffusion et stockage BLOB ou externe
- maintenance automatique via triggers, package PL/SQL et job `DBMS_SCHEDULER`

## Passage vers Laravel

Quand `composer` sera installé, le plus simple sera de migrer comme ceci :

- `vues/mise-en-page.php` -> `resources/views/layouts/app.blade.php`
- `vues/partiels/*` -> `resources/views/partials/*`
- `vues/pages/*` -> `resources/views/pages/*`
- `modeles/ModeleSite.php` -> service ou view model Laravel
- `controleurs/ControleurPages.php` -> `app/Http/Controllers/ControleurPages.php`
- `ressources/styles/style.css` -> `resources/css/app.css` ou Tailwind

## Stack cible conseillée

- site public : Laravel + Blade + Tailwind + Alpine
- zones riches : Vue 3 + Pinia seulement si un vrai état applicatif est utile
- base : Oracle via `yajra/laravel-oci8` quand l'environnement sera prêt

