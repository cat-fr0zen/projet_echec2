# Base de donnees

Ce dossier rassemble tout ce qui concerne la couche base de donnees du projet.

## Structure

- `migrations/` : schema Laravel pour MySQL ou MariaDB
- `seeders/` : donnees de reference
- `sql/` : scripts SQL manuels de creation et de verification
- `archives/oracle-19c-source/` : ancien schema Oracle garde en reference
- `oracle_to_mysql_review.md` : notes de migration Oracle vers MySQL ou MariaDB

## Regle de travail

- la base active du projet est MySQL ou MariaDB
- les changements de schema passent par les migrations Laravel
- les scripts SQL manuels servent uniquement a l'initialisation locale ou a la verification
