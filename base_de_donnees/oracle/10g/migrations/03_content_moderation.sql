/*
    Migration 03 - Articles et moderation
    But: tracer la categorie et documenter le flux de moderation.
*/

MERGE INTO schema_migration cible
USING (SELECT '10g.0.3' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.3',
    'content_moderation',
    'content',
    'Articles en attente, publies ou refuses; blocs normalises par article.'
);

COMMIT;
