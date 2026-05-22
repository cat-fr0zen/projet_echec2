/*
    Installation Oracle 10g - Cavaliers d'Herouville

    A lancer depuis SQL*Plus ou Oracle SQL Developer dans le dossier 10g:
    SQL> @install_10g.sql
*/

PROMPT === Creation du schema relationnel ===
@@schema.sql

PROMPT === Migrations par categorie ===
@@migrations/01_reference_data.sql
@@migrations/02_accounts_security.sql
@@migrations/03_content_moderation.sql
@@migrations/04_media_uploads.sql
@@migrations/05_orders_and_shop.sql
@@migrations/06_dammier_game.sql
@@migrations/07_club_schedule.sql
@@migrations/08_external_cache.sql

PROMPT === Verification securite ===
@@security_verify.sql
