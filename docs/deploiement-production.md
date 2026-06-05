# Deploiement production

## Objectif

Ce projet peut maintenant fonctionner avec un profil d'environnement public plus sur, mais il conserve une couche metier legacy. Avant mise en ligne, prevoir une recette complete.

## Variables d'environnement

- partir de `.env.example` pour la production
- partir de `.env.local.example` pour le developpement local
- generer un `APP_KEY` unique par environnement
- ne jamais committer de secret reel

## Base de donnees

- utiliser `database/sql/create_database_mysql_mariadb_production.sql`
- reserver `projet_echec2_app` au runtime applicatif
- reserver `projet_echec2_migration` aux migrations et deploys
- tester la restauration a partir d'une sauvegarde avant ouverture publique

## Sessions et HTTPS

- forcer HTTPS au niveau reverse proxy
- conserver `SESSION_SECURE_COOKIE=true`
- conserver `SESSION_ENCRYPT=true`
- verifier `SESSION_SAME_SITE=strict` apres recette navigateur

## Files d'attente et mail

- utiliser `QUEUE_CONNECTION=database` ou une file supervisee equivalente
- configurer `MAIL_MAILER=smtp`
- surveiller les echecs d'envoi et les jobs rates

## Health checks

- verifier `/up`
- verifier la page d'accueil publique
- verifier la connexion membre
- verifier le streaming des medias publies via `/fichiers/medias/*`

## Checklist avant publication

1. `composer check`
2. `php artisan route:list`
3. `php artisan test --testsuite=Project`
4. verifier les permissions d'ecriture de `storage/`
5. verifier les sauvegardes et le rollback BDD
6. verifier la recette clavier, modales et consentement
