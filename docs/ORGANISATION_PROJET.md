# Organisation du projet

Le workspace a ete simplifie pour garder un seul depot de travail.

## Ce qui reste important

- `Projet_echec2/` : depot principal
- `.codex/` : configuration Codex
- `.composer/` : configuration Composer locale, conservee si utile

## Logique de rangement dans le depot

- `app`, `config`, `routes`, `resources`, `public`, `storage` : application Laravel
- `database/migrations`, `database/seeders`, `database/sql` : base de donnees active
- `database/archives/oracle-19c-source` : reference historique Oracle
- `lancement` : scripts locaux lisibles
- `runtime/mysql-data` : donnees locales MySQL ou MariaDB sur cette machine

## Ce qui a ete retire

- backups workspace en double
- ancienne structure MVC separee
- anciens fichiers JSON de runtime
- scripts Oracle de lancement local
- sous-projets Laravel dupliques
