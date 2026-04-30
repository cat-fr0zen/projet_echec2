# Projet Echec 2

Prototype MVC en PHP pour un site d'association d'echecs avec espace membre local, cookies, theme clair/sombre et publication moderee.

## Ce qui a ete mis en place

- `index.php` comme front controller
- `routes.php` comme routeur unique pour toutes les URL
- `controleurs/PageController.php` pour le routage simple
- `controleurs/ActionController.php` pour les actions `POST`
- `modeles/SiteModel.php` pour les donnees du site
- `modeles/UserRepository.php` et `modeles/ArticleRepository.php` pour le stockage JSON local
- `vues/` pour le layout, les partiels et les pages
- `ressources/styles/style.css` et `ressources/scripts/site.js` pour le design, le motion system et les interactions

## Structure

```text
Projet_echec2/
|-- index.php
|-- routes.php
|-- start-server.ps1
|-- journaux/
|   |-- server-error.log
|   `-- server-output.log
|-- controleurs/
|   |-- ActionController.php
|   `-- PageController.php
|-- donnees/
|   |-- articles.json
|   `-- users.json
|-- modeles/
|   |-- ArticleRepository.php
|   |-- JsonStore.php
|   |-- SiteModel.php
|   `-- UserRepository.php
|-- ressources/
|   |-- scripts/
|   |   `-- site.js
|   `-- styles/
|       `-- style.css
`-- vues/
    |-- layout.php
    |-- partiels/
    |   |-- auth-modal.php
    |   |-- consent.php
    |   |-- footer.php
    |   `-- header.php
    `-- pages/
        |-- activities.php
        |-- articles.php
        |-- club.php
        |-- contact.php
        |-- guide.php
        |-- home.php
        |-- media-library.php
        |-- merch.php
        |-- not-found.php
        |-- profile.php
        `-- settings.php
```

## Routes disponibles

- `/`
- `/guide`
- `/mediatheque`
- `/articles`
- `/merch`
- `/club`
- `/activites`
- `/contact`
- `/profil`
- `/parametres`

## Lancement local

- lancer seulement `./start-server.ps1` depuis le dossier du projet
- ouvrir `http://127.0.0.1:8000/`
- le routeur unique est `routes.php`
- le point d'entree unique de l'application est `index.php`
- les logs serveur sont ecrits dans `journaux/`

## Direction design

- ambiance editorial / club historique
- palette ivoire, vert profond, laiton
- titres serif et corps tres lisible
- animations legeres et utiles, sans effet gadget

## Motion system

- `fade-up` a l'entree des sections
- `float` tres legere sur les badges du hero
- `hover-lift` sur les cartes et boutons
- popup membre, menu burger et switch de theme en JS natif
- prise en charge de `prefers-reduced-motion`

## Espace membre

- connexion et inscription dans une popup
- champs inscription : nom, prenom, date de naissance facultative, email, mot de passe, description
- profil editable apres connexion
- articles crees par les membres avec statut `pending_review`
- stockage local JSON pour le prototype

## Cookies et legal

- consentement obligatoire a l'entree du site
- cookie de theme `site_theme`
- cookie de consentement `site_consent`
- cookie de session PHP pour les membres connectes
- footer legal avec mentions legales, confidentialite et conditions d'utilisation

## Passage vers Laravel

Quand `composer` sera installe, le plus simple sera de migrer comme ceci :

- `vues/layout.php` -> `resources/views/layouts/app.blade.php`
- `vues/partiels/*` -> `resources/views/partials/*`
- `vues/pages/*` -> `resources/views/pages/*`
- `modeles/SiteModel.php` -> service ou view model Laravel
- `controleurs/PageController.php` -> `app/Http/Controllers/PageController.php`
- `ressources/styles/style.css` -> `resources/css/app.css` ou Tailwind

## Stack cible conseillee

- site public : Laravel + Blade + Tailwind + Alpine
- zones riches : Vue 3 + Pinia seulement si un vrai etat applicatif est utile
- base : Oracle via `yajra/laravel-oci8` quand l'environnement sera pret
