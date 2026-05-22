/*
    Migration 06 - Dammier et classement
*/

MERGE INTO schema_migration cible
USING (SELECT '10g.0.6' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.6',
    'dammier_game',
    'game',
    'Puzzles hebdomadaires et meilleurs scores par membre.'
);

COMMIT;
