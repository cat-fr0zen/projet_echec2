# Deploiement o2switch

## Preparer un dossier pret a envoyer

Depuis la racine du projet :

```bash
php scripts/prepare_o2switch_package.php
```

Le script genere automatiquement :

```text
build/o2switch-package/
```

avec trois zones separees :
- `app/`
- `public_html/`
- `storage/`

## Arborescence cible

```text
/home/joje0568/
|-- app/
|   |-- config.php
|   |-- mailer.php
|   |-- api_lichess.php
|   |-- api_chesscom.php
|   |-- cron/
|   |   |-- send_newsletter.php
|   |   `-- refresh_chess_data.php
|   `-- laravel/
|-- storage/
|   `-- pdfs/
`-- public_html/
    |-- .htaccess
    |-- index.php
    |-- admin/
    |-- upload_pdf.php
    |-- download_pdf.php
    `-- assets/
```

## Fichiers a envoyer

### Dans `/home/joje0568/app/laravel`

Envoyer le contenu de `build/o2switch-package/app/laravel/`.

### Dans `/home/joje0568/app`

Envoyer :
- `build/o2switch-package/app/config.php`
- `build/o2switch-package/app/mailer.php`
- `build/o2switch-package/app/api_lichess.php`
- `build/o2switch-package/app/api_chesscom.php`
- `build/o2switch-package/app/cron/send_newsletter.php`
- `build/o2switch-package/app/cron/refresh_chess_data.php`
- `build/o2switch-package/app/.env.o2switch.example`

### Dans `/home/joje0568/public_html`

Envoyer :
- `build/o2switch-package/public_html/index.php`
- `build/o2switch-package/public_html/.htaccess`
- `build/o2switch-package/public_html/upload_pdf.php`
- `build/o2switch-package/public_html/download_pdf.php`
- `build/o2switch-package/public_html/admin/index.php`
- `build/o2switch-package/public_html/assets/`

### Dans `/home/joje0568/storage`

Creer au minimum :

```text
/home/joje0568/storage/pdfs
```

## Base MySQL dans cPanel

1. Ouvrir `Bases de donnees MySQL`.
2. Creer une base, par exemple `joje0568_club`.
3. Creer un utilisateur MySQL dedie.
4. Associer l'utilisateur a la base avec `ALL PRIVILEGES`.
5. Reporter les valeurs dans `/home/joje0568/app/.env`.

## Import SQL dans phpMyAdmin

1. Importer d'abord le schema principal Laravel.
2. Importer ensuite `sql/o2switch_extra_tables.sql`.
3. Verifier la presence des tables :
   - `newsletter_subscribers`
   - `newsletter_campaigns`
   - `newsletter_queue`
   - `documents`
   - `api_cache`

## Configuration `.env`

Creer `/home/joje0568/app/.env` a partir de `.env.o2switch.example`, puis completer :
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM`

Points importants :
- garder `APP_ENV=production`
- garder `APP_DEBUG=false`
- garder `APP_FORCE_HTTPS=true`
- definir `LARAVEL_BASE_PATH=/home/joje0568/app/laravel`
- definir `PDF_STORAGE_PATH=/home/joje0568/storage/pdfs`
- garder `NEWSLETTER_DELIVERY_MODE=queue`

## Commandes serveur

Depuis le terminal cPanel ou en SSH :

```bash
cd /home/joje0568/app/laravel
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Taches cron a creer dans cPanel

```bash
*/5 * * * * /usr/local/bin/php -q /home/joje0568/app/cron/send_newsletter.php >/dev/null 2>&1

0 * * * * /usr/local/bin/php -q /home/joje0568/app/cron/refresh_chess_data.php >/dev/null 2>&1

* * * * * /usr/local/bin/php -q /home/joje0568/app/laravel/artisan schedule:run >/dev/null 2>&1
```

Le troisieme cron est necessaire pour les taches Laravel deja presentes, notamment le renouvellement annuel.

## Verifications avant mise en ligne

1. `https://cavaliersherouville.fr/up` repond.
2. Les pages publiques s'ouvrent sans erreur 500.
3. Connexion admin et deconnexion OK.
4. Upload PDF de test OK.
5. Telechargement PDF de test OK.
6. `php artisan mail:config-check` ne remonte pas d'identifiants manquants.
7. Un envoi test SMTP fonctionne.
8. Une campagne newsletter cree bien des lignes dans `newsletter_queue`.
9. Les scripts cron remplissent `api_cache`.
10. `storage/` et `bootstrap/cache/` sont inscriptibles.
