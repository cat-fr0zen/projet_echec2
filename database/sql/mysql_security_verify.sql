/*
    Verification lecture seule pour la base MySQL ou MariaDB du projet.
    A lancer apres les migrations et seeders si besoin.
*/

SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'compte_membre',
    'article',
    'article_bloc',
    'media_publication',
    'commande_locale',
    'dammier_puzzle',
    'dammier_score',
    'horaire_club',
    'horaire_creneau',
    'newsletter_abonnement',
    'newsletter_envoi'
  )
ORDER BY table_name;

SELECT table_name, constraint_name, constraint_type
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
ORDER BY table_name, constraint_type, constraint_name;

SELECT table_name, index_name, non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE()
ORDER BY table_name, index_name;
