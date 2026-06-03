# Lancement local

Scripts locaux pour faire tourner le projet avec MySQL ou MariaDB.

## Cible locale

- hote : `127.0.0.1`
- port : `3307`
- base : `projet_echec2`
- utilisateur : `root`
- mot de passe : vide

## Ordre recommande

1. `lancement\\demarrer_mysql_locale.bat`
2. `lancement\\installer_base_locale.bat`
3. `lancement\\lancer_site_local.bat`

## Detection des binaires

Les scripts cherchent automatiquement :

1. MariaDB Laragon
2. MySQL Laragon
3. MySQL ou MariaDB XAMPP

## Donnees locales

Le dossier de donnees local est :

`runtime\\mysql-data`

Il est garde dans le projet pour que l'environnement local soit lisible,
mais son contenu est ignore par Git.
