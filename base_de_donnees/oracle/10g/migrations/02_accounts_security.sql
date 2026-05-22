/*
    Migration 02 - Comptes et securite d'identite
    But: verifier les index metier sensibles et tracer la categorie.
*/

DECLARE
    v_index_count NUMBER;
BEGIN
    SELECT COUNT(*)
      INTO v_index_count
      FROM user_indexes
     WHERE index_name = 'UQ_COMPTE_MEMBRE_LICENCE_FFE';

    IF v_index_count = 0 THEN
        EXECUTE IMMEDIATE 'CREATE UNIQUE INDEX uq_compte_membre_licence_ffe ON compte_membre (UPPER(numero_licence_federale))';
    END IF;
END;
/

MERGE INTO schema_migration cible
USING (SELECT '10g.0.2' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.2', 'accounts_security', 'accounts', 'Unicite email et licence FFE.'
);

COMMIT;
