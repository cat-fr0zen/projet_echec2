PROMPT [2.1.1] Member federal license number

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
      INTO v_count
      FROM user_tab_columns
     WHERE table_name = 'COMPTE_MEMBRE'
       AND column_name = 'NUMERO_LICENCE_FEDERALE';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE
            'ALTER TABLE compte_membre ADD (numero_licence_federale VARCHAR2(30 CHAR) NULL)';
    END IF;
END;
/

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
      INTO v_count
      FROM user_constraints
     WHERE constraint_name = 'CK_COMPTE_MEMBRE_LICENCE_FFE'
       AND table_name = 'COMPTE_MEMBRE';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE
            'ALTER TABLE compte_membre ADD CONSTRAINT ck_compte_membre_licence_ffe ' ||
            'CHECK (numero_licence_federale IS NULL OR REGEXP_LIKE(numero_licence_federale, ''^[A-Za-z0-9-]{3,30}$''))';
    END IF;
END;
/

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
      INTO v_count
      FROM user_indexes
     WHERE index_name = 'UQ_COMPTE_MEMBRE_LICENCE_FFE';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE
            'CREATE UNIQUE INDEX uq_compte_membre_licence_ffe ' ||
            'ON compte_membre (CASE WHEN numero_licence_federale IS NOT NULL THEN UPPER(numero_licence_federale) END)';
    END IF;
END;
/

COMMENT ON COLUMN compte_membre.numero_licence_federale IS
    'Numero de licence federale facultatif, utilisable comme identifiant de connexion pour les adherents.';

CREATE OR REPLACE VIEW vw_admin_comptes AS
SELECT cm.identifiant_compte_membre,
       cm.adresse_courriel,
       cm.numero_licence_federale,
       rsc.code_statut AS code_statut_compte,
       rsc.libelle_statut AS libelle_statut_compte,
       pm.prenom,
       pm.nom,
       pm.date_naissance,
       pm.pseudo_chess_com,
       pm.accepte_affichage_public_profil,
       cm.derniere_connexion_le,
       LISTAGG(vr.code_role, ', ') WITHIN GROUP (ORDER BY vr.code_role) AS roles_actifs
  FROM compte_membre cm
  JOIN ref_statut_compte rsc
    ON rsc.identifiant_statut_compte = cm.identifiant_statut_compte
  LEFT JOIN profil_membre pm
    ON pm.identifiant_compte_membre = cm.identifiant_compte_membre
  LEFT JOIN vw_roles_comptes_actifs vr
    ON vr.identifiant_compte_membre = cm.identifiant_compte_membre
 GROUP BY cm.identifiant_compte_membre,
          cm.adresse_courriel,
          cm.numero_licence_federale,
          rsc.code_statut,
          rsc.libelle_statut,
          pm.prenom,
          pm.nom,
          pm.date_naissance,
          pm.pseudo_chess_com,
          pm.accepte_affichage_public_profil,
          cm.derniere_connexion_le;
/

GRANT SELECT ON vw_admin_comptes TO rl_site_admin_ops;
GRANT SELECT ON vw_admin_comptes TO rl_site_audit;

BEGIN
    pkg_schema_migration.enregistrer(
        '2.1.1',
        'Optional federal license number for member login',
        'schema'
    );
END;
/

COMMIT;
