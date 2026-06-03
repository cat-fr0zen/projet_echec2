# Base Oracle 19c

Ce dossier devient la cible officielle du site pour l'exploitation avec PHP 8.2 + `oci8_19`.

## Objectif

- Utiliser Oracle 19c comme version de reference.
- Garder le modele runtime compatible avec les depots PHP actuels.
- Garder un baseline unique facile a comprendre pour l'installation initiale.
- Eviter les privileges larges: pas de `GRANT DBA`, pas de droits `ANY`.

## Installation

Depuis SQL*Plus, SQLcl ou Oracle SQL Developer, place-toi dans ce dossier puis lance:

```sql
@install_19c.sql
```

Le script lance un pre-check, installe le schema baseline, puis lance `security_verify.sql`.

## Configuration PHP/XAMPP attendue

Dans `php.ini`, l'extension cible est:

```ini
extension=oci8_19
```

La variable `PATH` Windows doit contenir le dossier Instant Client 19c, par exemple:

```text
C:\oracle\instantclient_19_30
```

Verification rapide:

```powershell
php --ri oci8
php -r "echo oci_client_version(), PHP_EOL;"
```

Si PHP affiche une erreur du type `php_php_oci8_19.dll.dll`, la ligne `php.ini`
est probablement mal ecrite. Utilise `extension=oci8_19` et verifie que le
fichier `php_oci8_19.dll` existe bien dans `C:\xampp\php\ext`.

## Variables d'environnement

Le site lit la connexion via:

- `ORACLE_HOST`
- `ORACLE_PORT`
- `ORACLE_SERVICE`
- `ORACLE_USER`
- `ORACLE_PASSWORD`
- `ORACLE_CHARSET=AL32UTF8`
- `ORACLE_CLIENT_MIN_VERSION=19`

Le service peut etre `ORCLPDB1`, `XEPDB1`, `XE` ou le nom exact configure sur ton serveur.

## Securite

- Utiliser un schema applicatif dedie, pas `SYSTEM` ni `SYS`.
- Donner uniquement les droits necessaires au site.
- Garder les secrets dans l'environnement, jamais dans Git.
- Executer `security_verify.sql` apres chaque changement de base.

## Notes de maintenance

Le schema reste volontairement conservateur: les identifiants publics sont generes par PHP pour rester stables dans les URLs et les donnees existantes. Oracle 19c est utilise comme plateforme cible, sans obliger une reecriture risquee en colonnes `IDENTITY`.

Pour une evolution future, copie `change_journal_template.sql`, garde les sections `CHECK -> PLAN -> APPLY -> VERIFY -> ROLLBACK`, puis versionne le script obtenu.
