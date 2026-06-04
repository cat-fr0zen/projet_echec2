# Lancement local

Repere local pour faire tourner le projet avec MySQL ou MariaDB sans versionner de scripts machine-specifiques.

## Cible locale

- hote : `127.0.0.1`
- port : `3307`
- base : `projet_echec2`
- utilisateur recommande pour le dev : un compte dedie au projet
- mot de passe : garde-le dans `.env`, jamais dans Git

## Ordre recommande

1. Demarrer MySQL ou MariaDB depuis Laragon, XAMPP ou le service local de ton choix
2. Executer `database\\sql\\create_database_mysql_mariadb.sql`
3. Lancer `php artisan migrate --seed`
4. Lancer `php artisan serve`

## Commandes utiles

```powershell
php artisan migrate --seed
php artisan serve
```

## Lancement en un clic

Si tu veux tout lancer d'un coup sur cette machine, utilise :

`lancement\demarrer_tout_le_site.bat`

Ce script essaie de :

1. detecter PHP et MySQL de Laragon
2. installer Composer si `vendor` manque
3. verifier ou generer `APP_KEY`
4. demarrer MySQL local si besoin
5. verifier la base `projet_echec2`
6. lancer `migrate --seed`
7. lancer `php artisan serve`

## Lancement en plusieurs etapes

Si tu preferes faire simple, lance-les dans cet ordre :

1. `lancement\1_demarrer_mysql.bat`
2. `lancement\2_preparer_projet.bat`
3. `lancement\3_migrer_et_seed.bat`
4. `lancement\4_lancer_site.bat`

Le fichier commun `lancement\_charger_outils.bat` sert juste a detecter PHP, MySQL et lire `.env`.

## Donnees locales

Le dossier de donnees local suivi pour l'installation locale est :

`runtime\\mysql-data`

Le contenu de ce dossier est ignore par Git.

## Scripts locaux non versionnes

Si tu veux garder des `.bat` ou `.ps1` pour ta machine, place-les dans `lancement\\`.
Ils sont maintenant ignores par Git pour eviter de versionner de l'automatisation locale.
