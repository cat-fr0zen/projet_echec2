# Revue de conversion Oracle vers MySQL ou MariaDB

Source archivee :
`database/archives/oracle-19c-source/schema.sql`

Objectif :
conserver la trace de la conversion depuis Oracle 19c vers les migrations Laravel MySQL ou MariaDB, sans garder Oracle comme cible active du projet.

## Etat actuel

- la cible active du depot est `mysql`
- la base locale recommandee est `projet_echec2`
- le port local recommande est `3307`
- les migrations metier sont dans `database/migrations`
- les donnees de reference sont dans `database/seeders`

## Decision de structure

- Oracle est archive dans `database/archives/oracle-19c-source`
- MySQL ou MariaDB devient la base locale unique pour le developpement
- les anciennes structures hybrides ou doublons workspace ne sont plus la source de verite
