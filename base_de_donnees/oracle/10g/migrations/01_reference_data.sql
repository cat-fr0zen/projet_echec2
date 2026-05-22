/*
    Migration 01 - Donnees de reference
    Rejouable: oui, via MERGE.
*/

MERGE INTO ref_role_compte cible
USING (
    SELECT 'connecte' code_role, 'Compte connecte' libelle_role, 10 niveau_acces FROM dual
    UNION ALL SELECT 'adherent', 'Adherent', 50 FROM dual
    UNION ALL SELECT 'admin', 'Administrateur', 100 FROM dual
) source
ON (cible.code_role = source.code_role)
WHEN MATCHED THEN UPDATE SET cible.libelle_role = source.libelle_role, cible.niveau_acces = source.niveau_acces
WHEN NOT MATCHED THEN INSERT (code_role, libelle_role, niveau_acces)
VALUES (source.code_role, source.libelle_role, source.niveau_acces);

MERGE INTO ref_statut_compte cible
USING (
    SELECT 'actif' code_statut, 'Actif' libelle_statut FROM dual
    UNION ALL SELECT 'suspendu', 'Suspendu' FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut)
VALUES (source.code_statut, source.libelle_statut);

MERGE INTO ref_statut_adhesion cible
USING (
    SELECT 'aucune' code_statut, 'Non adherent' libelle_statut FROM dual
    UNION ALL SELECT 'active', 'Adhesion active' FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut)
VALUES (source.code_statut, source.libelle_statut);

MERGE INTO ref_statut_publication cible
USING (
    SELECT 'en_attente_validation' code_statut, 'En attente' libelle_statut FROM dual
    UNION ALL SELECT 'publie', 'Publie' FROM dual
    UNION ALL SELECT 'refuse', 'Refuse' FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut)
VALUES (source.code_statut, source.libelle_statut);

MERGE INTO ref_type_media cible
USING (
    SELECT 'photo' code_type, 'Photo' libelle_type FROM dual
    UNION ALL SELECT 'video', 'Video' FROM dual
) source
ON (cible.code_type = source.code_type)
WHEN MATCHED THEN UPDATE SET cible.libelle_type = source.libelle_type
WHEN NOT MATCHED THEN INSERT (code_type, libelle_type)
VALUES (source.code_type, source.libelle_type);

MERGE INTO ref_statut_commande cible
USING (
    SELECT 'en_attente' code_statut, 'En attente' libelle_statut FROM dual
    UNION ALL SELECT 'validee', 'Validee' FROM dual
    UNION ALL SELECT 'annulee', 'Annulee' FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut)
VALUES (source.code_statut, source.libelle_statut);

MERGE INTO ref_type_bloc_article cible
USING (
    SELECT 'paragraphe' code_type, 'Paragraphe' libelle_type FROM dual
    UNION ALL SELECT 'sous_titre', 'Sous-titre' FROM dual
    UNION ALL SELECT 'image', 'Image' FROM dual
    UNION ALL SELECT 'video', 'Video' FROM dual
) source
ON (cible.code_type = source.code_type)
WHEN MATCHED THEN UPDATE SET cible.libelle_type = source.libelle_type
WHEN NOT MATCHED THEN INSERT (code_type, libelle_type)
VALUES (source.code_type, source.libelle_type);

MERGE INTO schema_migration cible
USING (SELECT '10g.0.1' version_schema FROM dual) source
ON (cible.version_schema = source.version_schema)
WHEN NOT MATCHED THEN INSERT (
    version_schema, nom_migration, categorie, commentaire
) VALUES (
    '10g.0.1', 'reference_data', 'reference', 'Valeurs de reference rejouables.'
);

COMMIT;
