/*
    Migration 08 - Cache API externe en base
    Remplace les fichiers donnees/cache/*.json lorsque le cache applicatif est active.
*/

MERGE INTO schema_migration cible
USING (SELECT '10g.0.8' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.8',
    'external_cache',
    'cache',
    'Cache applicatif centralise en base Oracle.'
);

COMMIT;
