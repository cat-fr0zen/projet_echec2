# Projet Echec 2

Site Laravel du club d'echecs "Les Cavaliers d'Herouville".

Le depot a ete consolide pour ne garder qu'une seule base de travail :

- l'application Laravel active
- la structure de base MySQL ou MariaDB locale
- les migrations et seeders
- les sources Oracle conservees en archive documentaire

## Cible technique

- Application : Laravel
- Base locale : MySQL ou MariaDB
- Port base locale recommande : `3307`
- Nom de base recommande : `projet_echec2`
- Serveur web local : `php artisan serve`

## Organisation du projet

```text
Projet_echec2/
|-- app/                         Code applicatif Laravel
|-- config/                      Configuration du projet
|-- database/
|   |-- archives/
|   |   `-- oracle-19c-source/  Anciennes sources Oracle conservees en reference
|   |-- migrations/             Schema MySQL ou MariaDB versionne
|   |-- seeders/                Donnees de reference
|   |-- sql/                    Scripts SQL utilitaires
|   |-- README.md
|   `-- oracle_to_mysql_review.md
|-- docs/                       Documentation projet et exploitation locale
|-- lancement/                  Scripts de demarrage local
|-- public/                     Assets servis par Laravel
|-- resources/                  Vues Blade et ressources Laravel
|-- routes/                     Routes HTTP
|-- runtime/
|   `-- mysql-data/             Dossier local de donnees MySQL ou MariaDB
|-- storage/                    Stockage Laravel
|-- tests/                      Tests de structure du projet
|-- .env.example
|-- artisan
|-- composer.json
`-- README.md
```

## Flux local recommande

1. Demarrer la base locale avec `lancement\\demarrer_mysql_locale.bat`
2. Installer ou mettre a jour la base avec `lancement\\installer_base_locale.bat`
3. Lancer le site avec `lancement\\lancer_site_local.bat`
4. Ouvrir `http://127.0.0.1:8000`

Les scripts cherchent d'abord Laragon, puis XAMPP.

## Dossiers importants

- `database/migrations` : schema Laravel versionne
- `database/sql/create_database_mysql_mariadb.sql` : creation de la base locale
- `database/sql/mysql_security_verify.sql` : controles de verification apres migration
- `database/archives/oracle-19c-source` : archive de l'ancien schema Oracle
- `runtime/mysql-data` : donnees locales MySQL ou MariaDB sur cette machine

## Notes

- Le projet n'utilise plus Oracle comme base active.
- Les fichiers Oracle restants sont gardes uniquement comme reference de migration.
- Les anciens doublons de workspace et les anciennes couches MVC ou JSON ne sont plus la source de verite.

## Credits

- Mattheo Mullois
- Association Les Cavaliers d'Herouville
