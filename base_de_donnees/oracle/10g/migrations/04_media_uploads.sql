/*
    Migration 04 - Medias et uploads
    But: tracer la categorie et garder les metadonnees en base.
*/

MERGE INTO schema_migration cible
USING (SELECT '10g.0.4' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.4',
    'media_uploads',
    'media',
    'Metadonnees photo/video en base; fichiers binaires sous ressources/media/uploads.'
);

COMMIT;
