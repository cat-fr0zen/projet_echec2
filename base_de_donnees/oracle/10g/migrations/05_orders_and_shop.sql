/*
    Migration 05 - Boutique et commandes locales
*/

MERGE INTO schema_migration cible
USING (SELECT '10g.0.5' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.5',
    'orders_and_shop',
    'commerce',
    'Commandes locales sans paiement en ligne.'
);

COMMIT;
