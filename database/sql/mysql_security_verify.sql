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
    'dammier_solution_etape',
    'dammier_reponse_attendue',
    'dammier_indice',
    'dammier_score',
    'horaire_club',
    'horaire_creneau',
    'newsletter_abonnement',
    'newsletter_envoi',
    'ref_statut_newsletter_abonnement',
    'ref_type_evenement_newsletter',
    'ref_statut_envoi_newsletter'
  )
ORDER BY table_name;

SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'compte_membre' AND column_name = 'date_naissance')
    OR (table_name = 'article' AND column_name = 'contenu_plat_cache')
    OR (table_name = 'horaire_creneau' AND column_name IN ('heure_debut', 'heure_fin'))
    OR (table_name = 'newsletter_abonnement' AND column_name = 'code_statut')
    OR (table_name = 'newsletter_envoi' AND column_name IN ('code_type_evenement', 'code_statut_envoi'))
  )
ORDER BY table_name, column_name;

SELECT table_name, constraint_name, constraint_type
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
ORDER BY table_name, constraint_type, constraint_name;

SELECT table_name, index_name, non_unique
FROM information_schema.statistics
WHERE table_schema = DATABASE()
ORDER BY table_name, index_name;
