# Projet Echec 2

## Description du projet

Ce projet est un site web de club d'echecs.
Il permet de presenter le club, de publier des contenus, de gerer des comptes membres et de poser une base solide pour une vraie exploitation (activites, adhesion, mediatheque, boutique, conformite).

L'application actuelle est un projet PHP organise en MVC, avec une base Oracle preparee dans `base_de_donnees/oracle/BDD_echec_v1.sql`.

## Objectifs principaux

1. Donner une presence web claire au club.
2. Permettre aux membres de creer un compte et gerer leur profil.
3. Encadrer la publication d'articles avec moderation.
4. Gerer les contenus medias avec suivi des droits de diffusion.
5. Preparer une vraie gestion club: saisons, adhesions, activites, tournois, interclubs.
6. Poser un socle juridique et technique reutilisable en production.

## Pour qui ce projet est utilise

- Les visiteurs du site: consulter les pages publiques du club.
- Les membres: se connecter, modifier leur profil, proposer des articles.
- Les responsables du club: organiser les activites et la vie du club.
- Les administrateurs: moderer les contenus et piloter les donnees.

## A quelle fin il est utilise

- Communication du club (presentation, contact, infos utiles).
- Gestion de la relation membres (comptes, profils, consentements).
- Publication de contenu (articles, medias) avec workflow controle.
- Preparation d'une exploitation reelle type club de sport (adhesions, paiements, activites, tournois, rencontres interclubs).

## Arborescence du projet

```text
Projet_echec2/
|-- .gitignore
|-- index.php
|-- routeur.php
|-- README.md
|-- base_de_donnees/
|   `-- oracle/
|       |-- BDD_echec_v1.sql
|       `-- BDD_echec_v1.md
|-- donnees/
|   |-- articles.json
|   |-- utilisateurs.json
|   |-- cache/
|   `-- sessions/
|-- journaux/
|   |-- server-error.log
|   `-- server-output.log
|-- MVC/
|   |-- controleurs/
|   |   |-- ControleurActions.php
|   |   `-- ControleurPages.php
|   |-- modeles/
|   |   |-- DepotArticles.php
|   |   |-- DepotUtilisateurs.php
|   |   |-- ModeleSite.php
|   |   |-- ServiceChessCom.php
|   |   `-- StockageJson.php
|   `-- vues/
|       |-- mise-en-page.php
|       |-- pages/
|       `-- partiels/
`-- ressources/
    |-- media/
    |-- scripts/
    |   `-- site.js
    `-- styles/
        `-- style.css
```

## Langages utilises

- PHP: logique serveur (routing, controllers, modeles, vues).
- JavaScript: interactions front-end.
- CSS: styles et mise en forme.
- SQL / PL-SQL (Oracle): schema de base de donnees, vues, triggers, maintenance.
- Markdown: documentation projet.

## Logiciels et outils utilises

- Visual Studio Code (ou IDE equivalent): developpement.
- PHP (serveur local): execution du site.
- Oracle Database: base de donnees cible.
- Git: versioning du code.
- Navigateur web: test et validation du rendu.

## Credits

- Matthéo Mullois
- Association Les Cavaliers d'Hérouville

