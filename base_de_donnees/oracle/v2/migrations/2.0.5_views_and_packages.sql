PROMPT [2.0.5] Views and packages

CREATE OR REPLACE VIEW vw_roles_comptes_actifs AS
SELECT cr.identifiant_compte_membre,
       rr.code_role,
       rr.libelle_role,
       cr.debut_effet,
       cr.fin_effet
  FROM compte_role cr
  JOIN ref_role_compte rr
    ON rr.identifiant_role_compte = cr.identifiant_role_compte
 WHERE cr.debut_effet <= TRUNC(SYSDATE)
   AND (cr.fin_effet IS NULL OR cr.fin_effet >= TRUNC(SYSDATE));
/

CREATE OR REPLACE VIEW vw_admin_comptes AS
SELECT cm.identifiant_compte_membre,
       cm.adresse_courriel,
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
          rsc.code_statut,
          rsc.libelle_statut,
          pm.prenom,
          pm.nom,
          pm.date_naissance,
          pm.pseudo_chess_com,
          pm.accepte_affichage_public_profil,
          cm.derniere_connexion_le;
/

CREATE OR REPLACE VIEW vw_admin_articles AS
SELECT a.identifiant_article,
       a.titre,
       a.slug,
       rsa.code_statut,
       rsa.libelle_statut,
       a.soumis_le,
       a.publie_le,
       cm.adresse_courriel AS auteur_courriel,
       pm.prenom,
       pm.nom,
       COUNT(ra.identifiant_revision_article) AS total_revisions
  FROM article a
  JOIN ref_statut_article rsa
    ON rsa.identifiant_statut_article = a.identifiant_statut_article
  JOIN compte_membre cm
    ON cm.identifiant_compte_membre = a.identifiant_compte_auteur
  LEFT JOIN profil_membre pm
    ON pm.identifiant_compte_membre = cm.identifiant_compte_membre
  LEFT JOIN revision_article ra
    ON ra.identifiant_article = a.identifiant_article
 GROUP BY a.identifiant_article,
          a.titre,
          a.slug,
          rsa.code_statut,
          rsa.libelle_statut,
          a.soumis_le,
          a.publie_le,
          cm.adresse_courriel,
          pm.prenom,
          pm.nom;
/

CREATE OR REPLACE VIEW vw_admin_medias AS
SELECT rm.identifiant_ressource_media,
       rm.titre,
       rm.description_media,
       rtm.code_type_media,
       rsm.code_statut,
       rm.nom_fichier_original,
       rm.type_mime,
       rm.taille_octets,
       rm.valide_le,
       cm.adresse_courriel AS deposant_courriel,
       pm.prenom,
       pm.nom
  FROM ressource_media rm
  JOIN ref_type_media rtm
    ON rtm.identifiant_type_media = rm.identifiant_type_media
  JOIN ref_statut_media rsm
    ON rsm.identifiant_statut_media = rm.identifiant_statut_media
  LEFT JOIN compte_membre cm
    ON cm.identifiant_compte_membre = rm.identifiant_compte_depot
  LEFT JOIN profil_membre pm
    ON pm.identifiant_compte_membre = cm.identifiant_compte_membre;
/

CREATE OR REPLACE VIEW vw_ressources_media_pretes_publication AS
SELECT rm.identifiant_ressource_media,
       rm.titre,
       rm.description_media,
       rtm.code_type_media,
       refm.uri_stockage,
       rm.type_mime,
       rm.texte_alternatif
  FROM ressource_media rm
  JOIN ref_statut_media rsm
    ON rsm.identifiant_statut_media = rm.identifiant_statut_media
  JOIN ref_type_media rtm
    ON rtm.identifiant_type_media = rm.identifiant_type_media
  JOIN autorisation_droits_media adm
    ON adm.identifiant_ressource_media = rm.identifiant_ressource_media
  JOIN ref_statut_droits_media rsdm
    ON rsdm.identifiant_statut_droits_media = adm.identifiant_statut_droits_media
  LEFT JOIN reference_externe_media refm
    ON refm.identifiant_ressource_media = rm.identifiant_ressource_media
 WHERE rsm.code_statut = 'publie'
   AND rsdm.code_statut = 'accorde'
   AND (adm.expire_le IS NULL OR adm.expire_le >= SYSTIMESTAMP);
/

CREATE OR REPLACE VIEW vw_catalogue_boutique_public AS
SELECT p.identifiant_produit,
       p.titre,
       p.slug,
       p.description_courte,
       p.quantite_stock,
       rcp.libelle_categorie,
       pp.code_devise,
       pp.montant
  FROM produit p
  JOIN ref_statut_produit rsp
    ON rsp.identifiant_statut_produit = p.identifiant_statut_produit
  JOIN ref_categorie_produit rcp
    ON rcp.identifiant_categorie_produit = p.identifiant_categorie_produit
  JOIN prix_produit pp
    ON pp.identifiant_produit = p.identifiant_produit
 WHERE rsp.code_statut = 'actif'
   AND (p.disponible_a_partir_de IS NULL OR p.disponible_a_partir_de <= SYSTIMESTAMP)
   AND (p.disponible_jusqua IS NULL OR p.disponible_jusqua >= SYSTIMESTAMP)
   AND pp.applicable_a_partir_de = (
        SELECT MAX(pp2.applicable_a_partir_de)
          FROM prix_produit pp2
         WHERE pp2.identifiant_produit = pp.identifiant_produit
           AND pp2.code_devise = pp.code_devise
           AND pp2.applicable_a_partir_de <= SYSTIMESTAMP
           AND (pp2.applicable_jusqua IS NULL OR pp2.applicable_jusqua >= SYSTIMESTAMP)
   );
/

CREATE OR REPLACE VIEW vw_totaux_commande_client AS
SELECT lcc.identifiant_commande_client,
       SUM(lcc.quantite * lcc.prix_unitaire) AS montant_total
  FROM ligne_commande_client lcc
 GROUP BY lcc.identifiant_commande_client;
/

CREATE OR REPLACE VIEW vw_adhesions_actives AS
SELECT am.identifiant_adhesion_membre,
       am.identifiant_compte_membre,
       cm.adresse_courriel,
       pm.prenom,
       pm.nom,
       sc.code_saison,
       sc.libelle_saison,
       rta.libelle_type_adhesion,
       rsa.code_statut,
       am.montant_attendu,
       am.montant_paye,
       am.date_debut,
       am.date_expiration
  FROM adhesion_membre am
  JOIN compte_membre cm
    ON cm.identifiant_compte_membre = am.identifiant_compte_membre
  LEFT JOIN profil_membre pm
    ON pm.identifiant_compte_membre = cm.identifiant_compte_membre
  JOIN saison_club sc
    ON sc.identifiant_saison_club = am.identifiant_saison_club
  JOIN ref_type_adhesion rta
    ON rta.identifiant_type_adhesion = am.identifiant_type_adhesion
  JOIN ref_statut_adhesion rsa
    ON rsa.identifiant_statut_adhesion = am.identifiant_statut_adhesion
 WHERE rsa.code_statut = 'active';
/

CREATE OR REPLACE VIEW vw_calendrier_activites_public AS
SELECT sa.identifiant_session_activite,
       ac.titre AS activite_titre,
       rta.code_type_activite,
       rta.libelle_type_activite,
       sa.date_debut,
       sa.date_fin,
       sa.lieu,
       sa.places_max
  FROM session_activite sa
  JOIN activite_club ac
    ON ac.identifiant_activite_club = sa.identifiant_activite_club
  JOIN ref_type_activite rta
    ON rta.identifiant_type_activite = ac.identifiant_type_activite
 WHERE ac.est_visible_publiquement = 'Y'
   AND sa.date_fin >= SYSTIMESTAMP;
/

CREATE OR REPLACE PACKAGE pkg_schema_migration AS
    PROCEDURE enregistrer(
        p_version_schema VARCHAR2,
        p_description VARCHAR2,
        p_type_migration VARCHAR2 DEFAULT 'schema'
    );
END pkg_schema_migration;
/

CREATE OR REPLACE PACKAGE BODY pkg_schema_migration AS
    PROCEDURE enregistrer(
        p_version_schema VARCHAR2,
        p_description VARCHAR2,
        p_type_migration VARCHAR2 DEFAULT 'schema'
    ) IS
    BEGIN
        INSERT INTO schema_migration (
            version_schema,
            description_migration,
            type_migration
        )
        SELECT TRIM(p_version_schema),
               SUBSTR(TRIM(p_description), 1, 400),
               LOWER(TRIM(p_type_migration))
          FROM dual
         WHERE NOT EXISTS (
               SELECT 1
                 FROM schema_migration
                WHERE version_schema = TRIM(p_version_schema)
         );
        COMMIT;
    END enregistrer;
END pkg_schema_migration;
/

CREATE OR REPLACE PACKAGE pkg_site_admin AS
    PROCEDURE creer_compte_membre(
        p_email VARCHAR2,
        p_mot_de_passe_hache VARCHAR2,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_code_role_principal VARCHAR2 DEFAULT 'membre',
        p_code_statut_compte VARCHAR2 DEFAULT 'actif',
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N',
        p_attribue_par NUMBER DEFAULT NULL,
        o_identifiant_compte_membre OUT NUMBER
    );

    PROCEDURE attribuer_role_compte(
        p_identifiant_compte_membre NUMBER,
        p_code_role VARCHAR2,
        p_attribue_par NUMBER DEFAULT NULL,
        p_debut_effet DATE DEFAULT NULL,
        p_fin_effet DATE DEFAULT NULL,
        p_commentaire_attribution VARCHAR2 DEFAULT NULL
    );

    PROCEDURE mettre_a_jour_statut_compte(
        p_identifiant_compte_membre NUMBER,
        p_code_statut_compte VARCHAR2,
        p_modifie_par NUMBER DEFAULT NULL
    );

    PROCEDURE mettre_a_jour_profil_membre(
        p_identifiant_compte_membre NUMBER,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N',
        p_modifie_par NUMBER DEFAULT NULL
    );

    PROCEDURE enregistrer_consentement_membre(
        p_identifiant_compte_membre NUMBER,
        p_code_type_consentement VARCHAR2,
        p_code_document VARCHAR2,
        p_version_document VARCHAR2,
        p_adresse_ip VARCHAR2 DEFAULT NULL,
        p_agent_utilisateur VARCHAR2 DEFAULT NULL
    );

    PROCEDURE creer_article(
        p_identifiant_auteur_compte NUMBER,
        p_titre VARCHAR2,
        p_resume VARCHAR2,
        p_contenu CLOB,
        o_identifiant_article OUT NUMBER
    );

    PROCEDURE moderer_article(
        p_identifiant_article NUMBER,
        p_code_statut_article VARCHAR2,
        p_identifiant_relecteur NUMBER,
        p_code_decision_revision VARCHAR2,
        p_commentaire_revision CLOB DEFAULT NULL
    );

    PROCEDURE creer_media_externe(
        p_identifiant_deposant_compte NUMBER,
        p_code_type_media VARCHAR2,
        p_titre VARCHAR2,
        p_description_media VARCHAR2 DEFAULT NULL,
        p_uri_stockage VARCHAR2,
        p_fournisseur_stockage VARCHAR2,
        p_empreinte_sha256 VARCHAR2,
        p_type_mime VARCHAR2,
        p_taille_octets NUMBER,
        o_identifiant_ressource_media OUT NUMBER
    );

    PROCEDURE enregistrer_autorisation_media(
        p_identifiant_ressource_media NUMBER,
        p_nom_titulaire_droits VARCHAR2,
        p_perimetre_autorisation VARCHAR2,
        p_autorise_le TIMESTAMP WITH TIME ZONE,
        p_expire_le TIMESTAMP WITH TIME ZONE DEFAULT NULL,
        p_reference_consentement VARCHAR2 DEFAULT NULL,
        p_code_statut_droits_media VARCHAR2 DEFAULT 'accorde'
    );

    PROCEDURE moderer_media(
        p_identifiant_ressource_media NUMBER,
        p_code_statut_media VARCHAR2,
        p_identifiant_relecteur NUMBER,
        p_commentaire_revision CLOB DEFAULT NULL
    );

    PROCEDURE creer_produit(
        p_code_categorie VARCHAR2,
        p_titre VARCHAR2,
        p_slug VARCHAR2,
        p_description_courte VARCHAR2,
        p_description_longue CLOB DEFAULT NULL,
        p_reference_stock VARCHAR2 DEFAULT NULL,
        p_quantite_stock NUMBER DEFAULT 0,
        p_code_statut_produit VARCHAR2 DEFAULT 'brouillon',
        o_identifiant_produit OUT NUMBER
    );

    PROCEDURE definir_prix_produit(
        p_identifiant_produit NUMBER,
        p_code_devise VARCHAR2,
        p_montant NUMBER,
        p_applicable_a_partir_de TIMESTAMP WITH TIME ZONE DEFAULT NULL
    );

    PROCEDURE ouvrir_saison_club(
        p_code_saison VARCHAR2,
        p_libelle_saison VARCHAR2,
        p_date_debut DATE,
        p_date_fin DATE,
        p_activer CHAR DEFAULT 'N',
        o_identifiant_saison_club OUT NUMBER
    );

    PROCEDURE ouvrir_adhesion_membre(
        p_identifiant_compte_membre NUMBER,
        p_code_saison VARCHAR2,
        p_code_type_adhesion VARCHAR2,
        p_montant_attendu NUMBER DEFAULT NULL,
        p_code_statut_adhesion VARCHAR2 DEFAULT 'en_attente_paiement',
        o_identifiant_adhesion_membre OUT NUMBER
    );

    PROCEDURE enregistrer_paiement_adhesion(
        p_identifiant_adhesion_membre NUMBER,
        p_reference_paiement VARCHAR2,
        p_montant NUMBER,
        p_mode_paiement VARCHAR2,
        p_recu_le TIMESTAMP WITH TIME ZONE DEFAULT NULL
    );

    PROCEDURE creer_activite_club(
        p_code_type_activite VARCHAR2,
        p_titre VARCHAR2,
        p_description_activite CLOB DEFAULT NULL,
        p_code_niveau_min VARCHAR2 DEFAULT NULL,
        p_est_visible_publiquement CHAR DEFAULT 'Y',
        o_identifiant_activite_club OUT NUMBER
    );

    PROCEDURE programmer_session_activite(
        p_identifiant_activite_club NUMBER,
        p_date_debut TIMESTAMP WITH TIME ZONE,
        p_date_fin TIMESTAMP WITH TIME ZONE,
        p_lieu VARCHAR2,
        p_places_max NUMBER DEFAULT NULL,
        p_identifiant_compte_responsable NUMBER DEFAULT NULL,
        o_identifiant_session_activite OUT NUMBER
    );

    PROCEDURE inscrire_membre_session(
        p_identifiant_session_activite NUMBER,
        p_identifiant_compte_membre NUMBER,
        p_code_statut_inscription VARCHAR2 DEFAULT 'inscrit'
    );
END pkg_site_admin;
/

CREATE OR REPLACE PACKAGE BODY pkg_site_admin AS
    FUNCTION obtenir_id_statut_compte(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_compte
          INTO v_id
          FROM ref_statut_compte
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_compte;

    FUNCTION obtenir_id_role(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_role_compte
          INTO v_id
          FROM ref_role_compte
         WHERE code_role = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_role;

    FUNCTION obtenir_id_type_consentement(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_type_consentement
          INTO v_id
          FROM ref_type_consentement
         WHERE code_consentement = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_type_consentement;

    FUNCTION obtenir_id_document_version(
        p_code_document VARCHAR2,
        p_version_document VARCHAR2
    ) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT djv.identifiant_document_juridique_version
          INTO v_id
          FROM document_juridique_version djv
          JOIN type_document_juridique tdj
            ON tdj.identifiant_type_document_juridique = djv.identifiant_type_document_juridique
         WHERE tdj.code_document = LOWER(TRIM(p_code_document))
           AND djv.version_document = TRIM(p_version_document);
        RETURN v_id;
    END obtenir_id_document_version;

    FUNCTION obtenir_id_statut_article(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_article
          INTO v_id
          FROM ref_statut_article
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_article;

    FUNCTION obtenir_id_decision_article(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_decision_revision_article
          INTO v_id
          FROM ref_decision_revision_article
         WHERE code_decision = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_decision_article;

    FUNCTION obtenir_id_type_media(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_type_media
          INTO v_id
          FROM ref_type_media
         WHERE code_type_media = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_type_media;

    FUNCTION obtenir_id_mode_stockage_media(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_mode_stockage_media
          INTO v_id
          FROM ref_mode_stockage_media
         WHERE code_mode_stockage = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_mode_stockage_media;

    FUNCTION obtenir_id_statut_media(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_media
          INTO v_id
          FROM ref_statut_media
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_media;

    FUNCTION obtenir_id_statut_droits_media(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_droits_media
          INTO v_id
          FROM ref_statut_droits_media
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_droits_media;

    FUNCTION obtenir_id_categorie_produit(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_categorie_produit
          INTO v_id
          FROM ref_categorie_produit
         WHERE code_categorie = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_categorie_produit;

    FUNCTION obtenir_id_statut_produit(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_produit
          INTO v_id
          FROM ref_statut_produit
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_produit;

    FUNCTION obtenir_id_saison(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_saison_club
          INTO v_id
          FROM saison_club
         WHERE code_saison = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_saison;

    FUNCTION obtenir_id_type_adhesion(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_type_adhesion
          INTO v_id
          FROM ref_type_adhesion
         WHERE code_type_adhesion = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_type_adhesion;

    FUNCTION obtenir_id_statut_adhesion(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_adhesion
          INTO v_id
          FROM ref_statut_adhesion
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_adhesion;

    FUNCTION obtenir_id_type_activite(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_type_activite
          INTO v_id
          FROM ref_type_activite
         WHERE code_type_activite = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_type_activite;

    FUNCTION obtenir_id_niveau(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_niveau_joueur
          INTO v_id
          FROM ref_niveau_joueur
         WHERE code_niveau = LOWER(TRIM(p_code));
        RETURN v_id;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            RETURN NULL;
    END obtenir_id_niveau;

    FUNCTION obtenir_id_statut_session(p_code VARCHAR2) RETURN NUMBER IS
        v_id NUMBER;
    BEGIN
        SELECT identifiant_statut_inscription_session
          INTO v_id
          FROM ref_statut_inscription_session
         WHERE code_statut = LOWER(TRIM(p_code));
        RETURN v_id;
    END obtenir_id_statut_session;

    FUNCTION generer_slug(p_titre VARCHAR2) RETURN VARCHAR2 IS
        v_slug VARCHAR2(220 CHAR);
        v_total NUMBER := 0;
    BEGIN
        v_slug := LOWER(REGEXP_REPLACE(TRIM(p_titre), '[^[:alnum:]]+', '-'));
        v_slug := REGEXP_REPLACE(v_slug, '(^-+|-+$)', '');

        SELECT COUNT(*)
          INTO v_total
          FROM article
         WHERE slug = v_slug;

        IF v_total > 0 THEN
            v_slug := SUBSTR(v_slug, 1, 180) || '-' || TO_CHAR(SYSTIMESTAMP, 'YYYYMMDDHH24MISS');
        END IF;

        RETURN v_slug;
    END generer_slug;

    PROCEDURE journaliser_operation(
        p_identifiant_compte_operateur NUMBER,
        p_type_operation VARCHAR2,
        p_objet_cible VARCHAR2,
        p_cle_cible VARCHAR2,
        p_details_operation CLOB
    ) IS
    BEGIN
        INSERT INTO journal_audit_administration (
            identifiant_compte_operateur,
            type_operation,
            objet_cible,
            cle_cible,
            details_operation
        ) VALUES (
            p_identifiant_compte_operateur,
            SUBSTR(TRIM(p_type_operation), 1, 80),
            SUBSTR(TRIM(p_objet_cible), 1, 80),
            SUBSTR(TRIM(p_cle_cible), 1, 120),
            p_details_operation
        );
    END journaliser_operation;

    PROCEDURE attribuer_role_interne(
        p_identifiant_compte_membre NUMBER,
        p_code_role VARCHAR2,
        p_attribue_par NUMBER,
        p_debut_effet DATE,
        p_fin_effet DATE,
        p_commentaire_attribution VARCHAR2
    ) IS
        v_role_id NUMBER;
        v_debut DATE := NVL(p_debut_effet, TRUNC(SYSDATE));
        v_total NUMBER := 0;
    BEGIN
        v_role_id := obtenir_id_role(p_code_role);

        SELECT COUNT(*)
          INTO v_total
          FROM compte_role
         WHERE identifiant_compte_membre = p_identifiant_compte_membre
           AND identifiant_role_compte = v_role_id
           AND debut_effet = v_debut;

        IF v_total = 0 THEN
            INSERT INTO compte_role (
                identifiant_compte_membre,
                identifiant_role_compte,
                attribue_par_identifiant_compte,
                debut_effet,
                fin_effet,
                commentaire_attribution
            ) VALUES (
                p_identifiant_compte_membre,
                v_role_id,
                p_attribue_par,
                v_debut,
                p_fin_effet,
                p_commentaire_attribution
            );
        END IF;
    END attribuer_role_interne;

    PROCEDURE creer_compte_membre(
        p_email VARCHAR2,
        p_mot_de_passe_hache VARCHAR2,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_code_role_principal VARCHAR2 DEFAULT 'membre',
        p_code_statut_compte VARCHAR2 DEFAULT 'actif',
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N',
        p_attribue_par NUMBER DEFAULT NULL,
        o_identifiant_compte_membre OUT NUMBER
    ) IS
        v_statut_id NUMBER;
    BEGIN
        v_statut_id := obtenir_id_statut_compte(p_code_statut_compte);

        INSERT INTO compte_membre (
            adresse_courriel,
            mot_de_passe_hache,
            identifiant_statut_compte
        ) VALUES (
            LOWER(TRIM(p_email)),
            p_mot_de_passe_hache,
            v_statut_id
        )
        RETURNING identifiant_compte_membre INTO o_identifiant_compte_membre;

        INSERT INTO profil_membre (
            identifiant_compte_membre,
            prenom,
            nom,
            date_naissance,
            biographie,
            pseudo_chess_com,
            accepte_affichage_public_profil
        ) VALUES (
            o_identifiant_compte_membre,
            TRIM(p_prenom),
            TRIM(p_nom),
            p_date_naissance,
            p_biographie,
            NULLIF(LOWER(TRIM(p_pseudo_chess_com)), ''),
            CASE WHEN p_accepte_affichage_public = 'Y' THEN 'Y' ELSE 'N' END
        );

        attribuer_role_interne(
            o_identifiant_compte_membre,
            p_code_role_principal,
            p_attribue_par,
            TRUNC(SYSDATE),
            NULL,
            'Attribution automatique lors de la creation du compte'
        );

        journaliser_operation(
            p_attribue_par,
            'creer_compte_membre',
            'compte_membre',
            TO_CHAR(o_identifiant_compte_membre),
            'Creation d''un compte membre pour ' || LOWER(TRIM(p_email))
        );

        COMMIT;
    END creer_compte_membre;

    PROCEDURE attribuer_role_compte(
        p_identifiant_compte_membre NUMBER,
        p_code_role VARCHAR2,
        p_attribue_par NUMBER DEFAULT NULL,
        p_debut_effet DATE DEFAULT NULL,
        p_fin_effet DATE DEFAULT NULL,
        p_commentaire_attribution VARCHAR2 DEFAULT NULL
    ) IS
    BEGIN
        attribuer_role_interne(
            p_identifiant_compte_membre,
            p_code_role,
            p_attribue_par,
            p_debut_effet,
            p_fin_effet,
            p_commentaire_attribution
        );

        journaliser_operation(
            p_attribue_par,
            'attribuer_role_compte',
            'compte_role',
            TO_CHAR(p_identifiant_compte_membre),
            'Role attribue: ' || LOWER(TRIM(p_code_role))
        );

        COMMIT;
    END attribuer_role_compte;

    PROCEDURE mettre_a_jour_statut_compte(
        p_identifiant_compte_membre NUMBER,
        p_code_statut_compte VARCHAR2,
        p_modifie_par NUMBER DEFAULT NULL
    ) IS
        v_statut_id NUMBER;
    BEGIN
        v_statut_id := obtenir_id_statut_compte(p_code_statut_compte);

        UPDATE compte_membre
           SET identifiant_statut_compte = v_statut_id,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_compte_membre = p_identifiant_compte_membre;

        journaliser_operation(
            p_modifie_par,
            'mettre_a_jour_statut_compte',
            'compte_membre',
            TO_CHAR(p_identifiant_compte_membre),
            'Nouveau statut: ' || LOWER(TRIM(p_code_statut_compte))
        );

        COMMIT;
    END mettre_a_jour_statut_compte;

    PROCEDURE mettre_a_jour_profil_membre(
        p_identifiant_compte_membre NUMBER,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N',
        p_modifie_par NUMBER DEFAULT NULL
    ) IS
    BEGIN
        UPDATE profil_membre
           SET prenom = TRIM(p_prenom),
               nom = TRIM(p_nom),
               date_naissance = p_date_naissance,
               biographie = p_biographie,
               pseudo_chess_com = NULLIF(LOWER(TRIM(p_pseudo_chess_com)), ''),
               accepte_affichage_public_profil = CASE WHEN p_accepte_affichage_public = 'Y' THEN 'Y' ELSE 'N' END,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_compte_membre = p_identifiant_compte_membre;

        journaliser_operation(
            p_modifie_par,
            'mettre_a_jour_profil_membre',
            'profil_membre',
            TO_CHAR(p_identifiant_compte_membre),
            'Mise a jour du profil membre'
        );

        COMMIT;
    END mettre_a_jour_profil_membre;

    PROCEDURE enregistrer_consentement_membre(
        p_identifiant_compte_membre NUMBER,
        p_code_type_consentement VARCHAR2,
        p_code_document VARCHAR2,
        p_version_document VARCHAR2,
        p_adresse_ip VARCHAR2 DEFAULT NULL,
        p_agent_utilisateur VARCHAR2 DEFAULT NULL
    ) IS
        v_type_id NUMBER;
        v_document_id NUMBER;
    BEGIN
        v_type_id := obtenir_id_type_consentement(p_code_type_consentement);
        v_document_id := obtenir_id_document_version(p_code_document, p_version_document);

        INSERT INTO consentement_membre (
            identifiant_compte_membre,
            identifiant_type_consentement,
            identifiant_document_juridique_version,
            adresse_ip,
            agent_utilisateur
        ) VALUES (
            p_identifiant_compte_membre,
            v_type_id,
            v_document_id,
            p_adresse_ip,
            p_agent_utilisateur
        );

        journaliser_operation(
            p_identifiant_compte_membre,
            'enregistrer_consentement_membre',
            'consentement_membre',
            TO_CHAR(p_identifiant_compte_membre),
            'Consentement ' || LOWER(TRIM(p_code_type_consentement)) || ' version ' || TRIM(p_version_document)
        );

        COMMIT;
    END enregistrer_consentement_membre;

    PROCEDURE creer_article(
        p_identifiant_auteur_compte NUMBER,
        p_titre VARCHAR2,
        p_resume VARCHAR2,
        p_contenu CLOB,
        o_identifiant_article OUT NUMBER
    ) IS
        v_statut_id NUMBER;
        v_slug VARCHAR2(220 CHAR);
    BEGIN
        v_statut_id := obtenir_id_statut_article('en_attente_validation');
        v_slug := generer_slug(p_titre);

        INSERT INTO article (
            identifiant_compte_auteur,
            identifiant_statut_article,
            titre,
            slug,
            resume,
            contenu
        ) VALUES (
            p_identifiant_auteur_compte,
            v_statut_id,
            TRIM(p_titre),
            v_slug,
            TRIM(p_resume),
            p_contenu
        )
        RETURNING identifiant_article INTO o_identifiant_article;

        journaliser_operation(
            p_identifiant_auteur_compte,
            'creer_article',
            'article',
            TO_CHAR(o_identifiant_article),
            'Article cree: ' || TRIM(p_titre)
        );

        COMMIT;
    END creer_article;

    PROCEDURE moderer_article(
        p_identifiant_article NUMBER,
        p_code_statut_article VARCHAR2,
        p_identifiant_relecteur NUMBER,
        p_code_decision_revision VARCHAR2,
        p_commentaire_revision CLOB DEFAULT NULL
    ) IS
        v_statut_id NUMBER;
        v_decision_id NUMBER;
    BEGIN
        v_statut_id := obtenir_id_statut_article(p_code_statut_article);
        v_decision_id := obtenir_id_decision_article(p_code_decision_revision);

        UPDATE article
           SET identifiant_statut_article = v_statut_id,
               publie_le = CASE
                   WHEN LOWER(TRIM(p_code_statut_article)) = 'publie' THEN NVL(publie_le, SYSTIMESTAMP)
                   ELSE publie_le
               END,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_article = p_identifiant_article;

        INSERT INTO revision_article (
            identifiant_article,
            identifiant_compte_relecteur,
            identifiant_decision_revision_article,
            commentaire_revision
        ) VALUES (
            p_identifiant_article,
            p_identifiant_relecteur,
            v_decision_id,
            p_commentaire_revision
        );

        journaliser_operation(
            p_identifiant_relecteur,
            'moderer_article',
            'article',
            TO_CHAR(p_identifiant_article),
            'Statut article: ' || LOWER(TRIM(p_code_statut_article))
        );

        COMMIT;
    END moderer_article;

    PROCEDURE creer_media_externe(
        p_identifiant_deposant_compte NUMBER,
        p_code_type_media VARCHAR2,
        p_titre VARCHAR2,
        p_description_media VARCHAR2 DEFAULT NULL,
        p_uri_stockage VARCHAR2,
        p_fournisseur_stockage VARCHAR2,
        p_empreinte_sha256 VARCHAR2,
        p_type_mime VARCHAR2,
        p_taille_octets NUMBER,
        o_identifiant_ressource_media OUT NUMBER
    ) IS
        v_type_media_id NUMBER;
        v_mode_stockage_id NUMBER;
        v_statut_media_id NUMBER;
        v_nom_fichier VARCHAR2(255 CHAR);
    BEGIN
        v_type_media_id := obtenir_id_type_media(p_code_type_media);
        v_mode_stockage_id := obtenir_id_mode_stockage_media('uri_externe');
        v_statut_media_id := obtenir_id_statut_media('en_attente_validation');
        v_nom_fichier := REGEXP_SUBSTR(TRIM(p_uri_stockage), '[^/]+$');

        INSERT INTO ressource_media (
            identifiant_type_media,
            identifiant_mode_stockage_media,
            identifiant_statut_media,
            identifiant_compte_depot,
            titre,
            description_media,
            nom_fichier_original,
            type_mime,
            taille_octets
        ) VALUES (
            v_type_media_id,
            v_mode_stockage_id,
            v_statut_media_id,
            p_identifiant_deposant_compte,
            TRIM(p_titre),
            p_description_media,
            NVL(v_nom_fichier, SUBSTR(TRIM(p_titre), 1, 255)),
            TRIM(p_type_mime),
            p_taille_octets
        )
        RETURNING identifiant_ressource_media INTO o_identifiant_ressource_media;

        INSERT INTO reference_externe_media (
            identifiant_ressource_media,
            fournisseur_stockage,
            uri_stockage,
            empreinte_sha256
        ) VALUES (
            o_identifiant_ressource_media,
            TRIM(p_fournisseur_stockage),
            TRIM(p_uri_stockage),
            UPPER(TRIM(p_empreinte_sha256))
        );

        journaliser_operation(
            p_identifiant_deposant_compte,
            'creer_media_externe',
            'ressource_media',
            TO_CHAR(o_identifiant_ressource_media),
            'Media externe cree: ' || TRIM(p_titre)
        );

        COMMIT;
    END creer_media_externe;

    PROCEDURE enregistrer_autorisation_media(
        p_identifiant_ressource_media NUMBER,
        p_nom_titulaire_droits VARCHAR2,
        p_perimetre_autorisation VARCHAR2,
        p_autorise_le TIMESTAMP WITH TIME ZONE,
        p_expire_le TIMESTAMP WITH TIME ZONE DEFAULT NULL,
        p_reference_consentement VARCHAR2 DEFAULT NULL,
        p_code_statut_droits_media VARCHAR2 DEFAULT 'accorde'
    ) IS
        v_statut_id NUMBER;
    BEGIN
        v_statut_id := obtenir_id_statut_droits_media(p_code_statut_droits_media);

        INSERT INTO autorisation_droits_media (
            identifiant_ressource_media,
            identifiant_statut_droits_media,
            nom_titulaire_droits,
            reference_consentement,
            perimetre_autorisation,
            autorise_le,
            expire_le
        ) VALUES (
            p_identifiant_ressource_media,
            v_statut_id,
            TRIM(p_nom_titulaire_droits),
            p_reference_consentement,
            TRIM(p_perimetre_autorisation),
            p_autorise_le,
            p_expire_le
        );

        journaliser_operation(
            NULL,
            'enregistrer_autorisation_media',
            'autorisation_droits_media',
            TO_CHAR(p_identifiant_ressource_media),
            'Autorisation media enregistree'
        );

        COMMIT;
    END enregistrer_autorisation_media;

    PROCEDURE moderer_media(
        p_identifiant_ressource_media NUMBER,
        p_code_statut_media VARCHAR2,
        p_identifiant_relecteur NUMBER,
        p_commentaire_revision CLOB DEFAULT NULL
    ) IS
        v_statut_id NUMBER;
    BEGIN
        v_statut_id := obtenir_id_statut_media(p_code_statut_media);

        UPDATE ressource_media
           SET identifiant_statut_media = v_statut_id,
               valide_par_identifiant_compte = p_identifiant_relecteur,
               valide_le = CASE
                   WHEN LOWER(TRIM(p_code_statut_media)) = 'publie' THEN SYSTIMESTAMP
                   ELSE valide_le
               END,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_ressource_media = p_identifiant_ressource_media;

        INSERT INTO revision_media (
            identifiant_ressource_media,
            identifiant_compte_relecteur,
            identifiant_statut_media,
            commentaire_revision
        ) VALUES (
            p_identifiant_ressource_media,
            p_identifiant_relecteur,
            v_statut_id,
            p_commentaire_revision
        );

        journaliser_operation(
            p_identifiant_relecteur,
            'moderer_media',
            'ressource_media',
            TO_CHAR(p_identifiant_ressource_media),
            'Statut media: ' || LOWER(TRIM(p_code_statut_media))
        );

        COMMIT;
    END moderer_media;

    PROCEDURE creer_produit(
        p_code_categorie VARCHAR2,
        p_titre VARCHAR2,
        p_slug VARCHAR2,
        p_description_courte VARCHAR2,
        p_description_longue CLOB DEFAULT NULL,
        p_reference_stock VARCHAR2 DEFAULT NULL,
        p_quantite_stock NUMBER DEFAULT 0,
        p_code_statut_produit VARCHAR2 DEFAULT 'brouillon',
        o_identifiant_produit OUT NUMBER
    ) IS
        v_categorie_id NUMBER;
        v_statut_id NUMBER;
    BEGIN
        v_categorie_id := obtenir_id_categorie_produit(p_code_categorie);
        v_statut_id := obtenir_id_statut_produit(p_code_statut_produit);

        INSERT INTO produit (
            identifiant_categorie_produit,
            identifiant_statut_produit,
            titre,
            slug,
            reference_stock,
            description_courte,
            description_longue,
            quantite_stock
        ) VALUES (
            v_categorie_id,
            v_statut_id,
            TRIM(p_titre),
            LOWER(TRIM(p_slug)),
            NULLIF(TRIM(p_reference_stock), ''),
            TRIM(p_description_courte),
            p_description_longue,
            NVL(p_quantite_stock, 0)
        )
        RETURNING identifiant_produit INTO o_identifiant_produit;

        journaliser_operation(
            NULL,
            'creer_produit',
            'produit',
            TO_CHAR(o_identifiant_produit),
            'Produit cree: ' || TRIM(p_titre)
        );

        COMMIT;
    END creer_produit;

    PROCEDURE definir_prix_produit(
        p_identifiant_produit NUMBER,
        p_code_devise VARCHAR2,
        p_montant NUMBER,
        p_applicable_a_partir_de TIMESTAMP WITH TIME ZONE DEFAULT NULL
    ) IS
        v_debut TIMESTAMP WITH TIME ZONE := NVL(p_applicable_a_partir_de, SYSTIMESTAMP);
    BEGIN
        UPDATE prix_produit
           SET applicable_jusqua = v_debut
         WHERE identifiant_produit = p_identifiant_produit
           AND code_devise = UPPER(TRIM(p_code_devise))
           AND applicable_jusqua IS NULL;

        INSERT INTO prix_produit (
            identifiant_produit,
            code_devise,
            montant,
            applicable_a_partir_de
        ) VALUES (
            p_identifiant_produit,
            UPPER(TRIM(p_code_devise)),
            p_montant,
            v_debut
        );

        journaliser_operation(
            NULL,
            'definir_prix_produit',
            'prix_produit',
            TO_CHAR(p_identifiant_produit),
            'Prix defini en devise ' || UPPER(TRIM(p_code_devise))
        );

        COMMIT;
    END definir_prix_produit;

    PROCEDURE ouvrir_saison_club(
        p_code_saison VARCHAR2,
        p_libelle_saison VARCHAR2,
        p_date_debut DATE,
        p_date_fin DATE,
        p_activer CHAR DEFAULT 'N',
        o_identifiant_saison_club OUT NUMBER
    ) IS
    BEGIN
        IF p_activer = 'Y' THEN
            UPDATE saison_club
               SET est_saison_active = 'N',
                   mis_a_jour_le = SYSTIMESTAMP
             WHERE est_saison_active = 'Y';
        END IF;

        INSERT INTO saison_club (
            code_saison,
            libelle_saison,
            date_debut,
            date_fin,
            est_saison_active
        ) VALUES (
            LOWER(TRIM(p_code_saison)),
            TRIM(p_libelle_saison),
            p_date_debut,
            p_date_fin,
            CASE WHEN p_activer = 'Y' THEN 'Y' ELSE 'N' END
        )
        RETURNING identifiant_saison_club INTO o_identifiant_saison_club;

        journaliser_operation(
            NULL,
            'ouvrir_saison_club',
            'saison_club',
            TO_CHAR(o_identifiant_saison_club),
            'Saison creee: ' || LOWER(TRIM(p_code_saison))
        );

        COMMIT;
    END ouvrir_saison_club;

    PROCEDURE ouvrir_adhesion_membre(
        p_identifiant_compte_membre NUMBER,
        p_code_saison VARCHAR2,
        p_code_type_adhesion VARCHAR2,
        p_montant_attendu NUMBER DEFAULT NULL,
        p_code_statut_adhesion VARCHAR2 DEFAULT 'en_attente_paiement',
        o_identifiant_adhesion_membre OUT NUMBER
    ) IS
        v_saison_id NUMBER;
        v_type_id NUMBER;
        v_statut_id NUMBER;
        v_date_debut DATE;
        v_date_fin DATE;
        v_montant NUMBER(10,2);
    BEGIN
        v_saison_id := obtenir_id_saison(p_code_saison);
        v_type_id := obtenir_id_type_adhesion(p_code_type_adhesion);
        v_statut_id := obtenir_id_statut_adhesion(p_code_statut_adhesion);

        SELECT date_debut, date_fin
          INTO v_date_debut, v_date_fin
          FROM saison_club
         WHERE identifiant_saison_club = v_saison_id;

        IF p_montant_attendu IS NULL THEN
            SELECT montant_base
              INTO v_montant
              FROM ref_type_adhesion
             WHERE identifiant_type_adhesion = v_type_id;
        ELSE
            v_montant := p_montant_attendu;
        END IF;

        INSERT INTO adhesion_membre (
            identifiant_compte_membre,
            identifiant_saison_club,
            identifiant_type_adhesion,
            identifiant_statut_adhesion,
            date_debut,
            date_expiration,
            montant_attendu
        ) VALUES (
            p_identifiant_compte_membre,
            v_saison_id,
            v_type_id,
            v_statut_id,
            v_date_debut,
            v_date_fin,
            v_montant
        )
        RETURNING identifiant_adhesion_membre INTO o_identifiant_adhesion_membre;

        journaliser_operation(
            NULL,
            'ouvrir_adhesion_membre',
            'adhesion_membre',
            TO_CHAR(o_identifiant_adhesion_membre),
            'Adhesion ouverte pour le compte ' || TO_CHAR(p_identifiant_compte_membre)
        );

        COMMIT;
    END ouvrir_adhesion_membre;

    PROCEDURE enregistrer_paiement_adhesion(
        p_identifiant_adhesion_membre NUMBER,
        p_reference_paiement VARCHAR2,
        p_montant NUMBER,
        p_mode_paiement VARCHAR2,
        p_recu_le TIMESTAMP WITH TIME ZONE DEFAULT NULL
    ) IS
        v_total_paye NUMBER(10,2);
        v_montant_attendu NUMBER(10,2);
        v_statut_active NUMBER;
    BEGIN
        INSERT INTO paiement_adhesion (
            identifiant_adhesion_membre,
            reference_paiement,
            montant,
            mode_paiement,
            recu_le
        ) VALUES (
            p_identifiant_adhesion_membre,
            TRIM(p_reference_paiement),
            p_montant,
            TRIM(p_mode_paiement),
            NVL(p_recu_le, SYSTIMESTAMP)
        );

        SELECT NVL(SUM(montant), 0)
          INTO v_total_paye
          FROM paiement_adhesion
         WHERE identifiant_adhesion_membre = p_identifiant_adhesion_membre;

        SELECT montant_attendu
          INTO v_montant_attendu
          FROM adhesion_membre
         WHERE identifiant_adhesion_membre = p_identifiant_adhesion_membre;

        UPDATE adhesion_membre
           SET montant_paye = v_total_paye,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_adhesion_membre = p_identifiant_adhesion_membre;

        IF v_total_paye >= v_montant_attendu THEN
            v_statut_active := obtenir_id_statut_adhesion('active');

            UPDATE adhesion_membre
               SET identifiant_statut_adhesion = v_statut_active,
                   mis_a_jour_le = SYSTIMESTAMP
             WHERE identifiant_adhesion_membre = p_identifiant_adhesion_membre;
        END IF;

        journaliser_operation(
            NULL,
            'enregistrer_paiement_adhesion',
            'paiement_adhesion',
            TO_CHAR(p_identifiant_adhesion_membre),
            'Paiement adhesion enregistre'
        );

        COMMIT;
    END enregistrer_paiement_adhesion;

    PROCEDURE creer_activite_club(
        p_code_type_activite VARCHAR2,
        p_titre VARCHAR2,
        p_description_activite CLOB DEFAULT NULL,
        p_code_niveau_min VARCHAR2 DEFAULT NULL,
        p_est_visible_publiquement CHAR DEFAULT 'Y',
        o_identifiant_activite_club OUT NUMBER
    ) IS
        v_type_id NUMBER;
        v_niveau_id NUMBER;
    BEGIN
        v_type_id := obtenir_id_type_activite(p_code_type_activite);
        v_niveau_id := CASE
            WHEN p_code_niveau_min IS NULL THEN NULL
            ELSE obtenir_id_niveau(p_code_niveau_min)
        END;

        INSERT INTO activite_club (
            identifiant_type_activite,
            identifiant_niveau_joueur_minimum,
            titre,
            description_activite,
            est_visible_publiquement
        ) VALUES (
            v_type_id,
            v_niveau_id,
            TRIM(p_titre),
            p_description_activite,
            CASE WHEN p_est_visible_publiquement = 'N' THEN 'N' ELSE 'Y' END
        )
        RETURNING identifiant_activite_club INTO o_identifiant_activite_club;

        journaliser_operation(
            NULL,
            'creer_activite_club',
            'activite_club',
            TO_CHAR(o_identifiant_activite_club),
            'Activite creee: ' || TRIM(p_titre)
        );

        COMMIT;
    END creer_activite_club;

    PROCEDURE programmer_session_activite(
        p_identifiant_activite_club NUMBER,
        p_date_debut TIMESTAMP WITH TIME ZONE,
        p_date_fin TIMESTAMP WITH TIME ZONE,
        p_lieu VARCHAR2,
        p_places_max NUMBER DEFAULT NULL,
        p_identifiant_compte_responsable NUMBER DEFAULT NULL,
        o_identifiant_session_activite OUT NUMBER
    ) IS
    BEGIN
        INSERT INTO session_activite (
            identifiant_activite_club,
            identifiant_compte_responsable,
            date_debut,
            date_fin,
            lieu,
            places_max
        ) VALUES (
            p_identifiant_activite_club,
            p_identifiant_compte_responsable,
            p_date_debut,
            p_date_fin,
            TRIM(p_lieu),
            p_places_max
        )
        RETURNING identifiant_session_activite INTO o_identifiant_session_activite;

        journaliser_operation(
            p_identifiant_compte_responsable,
            'programmer_session_activite',
            'session_activite',
            TO_CHAR(o_identifiant_session_activite),
            'Session programmee'
        );

        COMMIT;
    END programmer_session_activite;

    PROCEDURE inscrire_membre_session(
        p_identifiant_session_activite NUMBER,
        p_identifiant_compte_membre NUMBER,
        p_code_statut_inscription VARCHAR2 DEFAULT 'inscrit'
    ) IS
        v_statut_id NUMBER;
    BEGIN
        v_statut_id := obtenir_id_statut_session(p_code_statut_inscription);

        INSERT INTO inscription_session_activite (
            identifiant_session_activite,
            identifiant_compte_membre,
            identifiant_statut_inscription_session
        ) VALUES (
            p_identifiant_session_activite,
            p_identifiant_compte_membre,
            v_statut_id
        );

        journaliser_operation(
            p_identifiant_compte_membre,
            'inscrire_membre_session',
            'inscription_session_activite',
            TO_CHAR(p_identifiant_session_activite),
            'Inscription session effectuee'
        );

        COMMIT;
    END inscrire_membre_session;
END pkg_site_admin;
/

CREATE OR REPLACE PACKAGE pkg_site_portail AS
    PROCEDURE creer_compte_membre(
        p_email VARCHAR2,
        p_mot_de_passe_hache VARCHAR2,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N',
        o_identifiant_compte_membre OUT NUMBER
    );

    PROCEDURE mettre_a_jour_profil_membre(
        p_identifiant_compte_membre NUMBER,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N'
    );

    PROCEDURE enregistrer_consentement_membre(
        p_identifiant_compte_membre NUMBER,
        p_code_type_consentement VARCHAR2,
        p_code_document VARCHAR2,
        p_version_document VARCHAR2,
        p_adresse_ip VARCHAR2 DEFAULT NULL,
        p_agent_utilisateur VARCHAR2 DEFAULT NULL
    );

    PROCEDURE creer_article(
        p_identifiant_auteur_compte NUMBER,
        p_titre VARCHAR2,
        p_resume VARCHAR2,
        p_contenu CLOB,
        o_identifiant_article OUT NUMBER
    );

    PROCEDURE creer_media_externe(
        p_identifiant_deposant_compte NUMBER,
        p_code_type_media VARCHAR2,
        p_titre VARCHAR2,
        p_description_media VARCHAR2 DEFAULT NULL,
        p_uri_stockage VARCHAR2,
        p_fournisseur_stockage VARCHAR2,
        p_empreinte_sha256 VARCHAR2,
        p_type_mime VARCHAR2,
        p_taille_octets NUMBER,
        o_identifiant_ressource_media OUT NUMBER
    );
END pkg_site_portail;
/

CREATE OR REPLACE PACKAGE BODY pkg_site_portail AS
    PROCEDURE creer_compte_membre(
        p_email VARCHAR2,
        p_mot_de_passe_hache VARCHAR2,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N',
        o_identifiant_compte_membre OUT NUMBER
    ) IS
    BEGIN
        pkg_site_admin.creer_compte_membre(
            p_email => p_email,
            p_mot_de_passe_hache => p_mot_de_passe_hache,
            p_prenom => p_prenom,
            p_nom => p_nom,
            p_code_role_principal => 'membre',
            p_code_statut_compte => 'actif',
            p_date_naissance => p_date_naissance,
            p_biographie => p_biographie,
            p_pseudo_chess_com => p_pseudo_chess_com,
            p_accepte_affichage_public => p_accepte_affichage_public,
            p_attribue_par => NULL,
            o_identifiant_compte_membre => o_identifiant_compte_membre
        );
    END creer_compte_membre;

    PROCEDURE mettre_a_jour_profil_membre(
        p_identifiant_compte_membre NUMBER,
        p_prenom VARCHAR2,
        p_nom VARCHAR2,
        p_date_naissance DATE DEFAULT NULL,
        p_biographie CLOB DEFAULT NULL,
        p_pseudo_chess_com VARCHAR2 DEFAULT NULL,
        p_accepte_affichage_public CHAR DEFAULT 'N'
    ) IS
    BEGIN
        pkg_site_admin.mettre_a_jour_profil_membre(
            p_identifiant_compte_membre => p_identifiant_compte_membre,
            p_prenom => p_prenom,
            p_nom => p_nom,
            p_date_naissance => p_date_naissance,
            p_biographie => p_biographie,
            p_pseudo_chess_com => p_pseudo_chess_com,
            p_accepte_affichage_public => p_accepte_affichage_public,
            p_modifie_par => p_identifiant_compte_membre
        );
    END mettre_a_jour_profil_membre;

    PROCEDURE enregistrer_consentement_membre(
        p_identifiant_compte_membre NUMBER,
        p_code_type_consentement VARCHAR2,
        p_code_document VARCHAR2,
        p_version_document VARCHAR2,
        p_adresse_ip VARCHAR2 DEFAULT NULL,
        p_agent_utilisateur VARCHAR2 DEFAULT NULL
    ) IS
    BEGIN
        pkg_site_admin.enregistrer_consentement_membre(
            p_identifiant_compte_membre => p_identifiant_compte_membre,
            p_code_type_consentement => p_code_type_consentement,
            p_code_document => p_code_document,
            p_version_document => p_version_document,
            p_adresse_ip => p_adresse_ip,
            p_agent_utilisateur => p_agent_utilisateur
        );
    END enregistrer_consentement_membre;

    PROCEDURE creer_article(
        p_identifiant_auteur_compte NUMBER,
        p_titre VARCHAR2,
        p_resume VARCHAR2,
        p_contenu CLOB,
        o_identifiant_article OUT NUMBER
    ) IS
    BEGIN
        pkg_site_admin.creer_article(
            p_identifiant_auteur_compte => p_identifiant_auteur_compte,
            p_titre => p_titre,
            p_resume => p_resume,
            p_contenu => p_contenu,
            o_identifiant_article => o_identifiant_article
        );
    END creer_article;

    PROCEDURE creer_media_externe(
        p_identifiant_deposant_compte NUMBER,
        p_code_type_media VARCHAR2,
        p_titre VARCHAR2,
        p_description_media VARCHAR2 DEFAULT NULL,
        p_uri_stockage VARCHAR2,
        p_fournisseur_stockage VARCHAR2,
        p_empreinte_sha256 VARCHAR2,
        p_type_mime VARCHAR2,
        p_taille_octets NUMBER,
        o_identifiant_ressource_media OUT NUMBER
    ) IS
    BEGIN
        pkg_site_admin.creer_media_externe(
            p_identifiant_deposant_compte => p_identifiant_deposant_compte,
            p_code_type_media => p_code_type_media,
            p_titre => p_titre,
            p_description_media => p_description_media,
            p_uri_stockage => p_uri_stockage,
            p_fournisseur_stockage => p_fournisseur_stockage,
            p_empreinte_sha256 => p_empreinte_sha256,
            p_type_mime => p_type_mime,
            p_taille_octets => p_taille_octets,
            o_identifiant_ressource_media => o_identifiant_ressource_media
        );
    END creer_media_externe;
END pkg_site_portail;
/

CREATE OR REPLACE PACKAGE pkg_maintenance_site AS
    PROCEDURE cloturer_droits_media_expires;
    PROCEDURE archiver_articles_non_traites(p_seuil_jours NUMBER DEFAULT 180);
    PROCEDURE expirer_catalogue_boutique;
    PROCEDURE clore_adhesions_expirees;
    PROCEDURE marquer_tournois_termines;
    PROCEDURE lancer_maintenance_quotidienne;
END pkg_maintenance_site;
/

CREATE OR REPLACE PACKAGE BODY pkg_maintenance_site AS
    PROCEDURE cloturer_droits_media_expires IS
        v_statut_expire NUMBER;
        v_statut_accorde NUMBER;
    BEGIN
        SELECT identifiant_statut_droits_media
          INTO v_statut_expire
          FROM ref_statut_droits_media
         WHERE code_statut = 'expire';

        SELECT identifiant_statut_droits_media
          INTO v_statut_accorde
          FROM ref_statut_droits_media
         WHERE code_statut = 'accorde';

        UPDATE autorisation_droits_media
           SET identifiant_statut_droits_media = v_statut_expire,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_droits_media = v_statut_accorde
           AND expire_le IS NOT NULL
           AND expire_le < SYSTIMESTAMP;
    END cloturer_droits_media_expires;

    PROCEDURE archiver_articles_non_traites(p_seuil_jours NUMBER DEFAULT 180) IS
        v_statut_attente NUMBER;
        v_statut_archive NUMBER;
    BEGIN
        SELECT identifiant_statut_article
          INTO v_statut_attente
          FROM ref_statut_article
         WHERE code_statut = 'en_attente_validation';

        SELECT identifiant_statut_article
          INTO v_statut_archive
          FROM ref_statut_article
         WHERE code_statut = 'archive';

        UPDATE article
           SET identifiant_statut_article = v_statut_archive,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_article = v_statut_attente
           AND soumis_le < SYSTIMESTAMP - NUMTODSINTERVAL(p_seuil_jours, 'DAY');
    END archiver_articles_non_traites;

    PROCEDURE expirer_catalogue_boutique IS
        v_statut_actif NUMBER;
        v_statut_expire NUMBER;
    BEGIN
        SELECT identifiant_statut_produit
          INTO v_statut_actif
          FROM ref_statut_produit
         WHERE code_statut = 'actif';

        SELECT identifiant_statut_produit
          INTO v_statut_expire
          FROM ref_statut_produit
         WHERE code_statut = 'expire';

        UPDATE produit
           SET identifiant_statut_produit = v_statut_expire,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_produit = v_statut_actif
           AND disponible_jusqua IS NOT NULL
           AND disponible_jusqua < SYSTIMESTAMP;
    END expirer_catalogue_boutique;

    PROCEDURE clore_adhesions_expirees IS
        v_statut_active NUMBER;
        v_statut_expiree NUMBER;
    BEGIN
        SELECT identifiant_statut_adhesion
          INTO v_statut_active
          FROM ref_statut_adhesion
         WHERE code_statut = 'active';

        SELECT identifiant_statut_adhesion
          INTO v_statut_expiree
          FROM ref_statut_adhesion
         WHERE code_statut = 'expiree';

        UPDATE adhesion_membre
           SET identifiant_statut_adhesion = v_statut_expiree,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_adhesion = v_statut_active
           AND date_expiration < TRUNC(SYSDATE);
    END clore_adhesions_expirees;

    PROCEDURE marquer_tournois_termines IS
        v_statut_en_cours NUMBER;
        v_statut_termine NUMBER;
    BEGIN
        SELECT identifiant_statut_tournoi
          INTO v_statut_en_cours
          FROM ref_statut_tournoi
         WHERE code_statut = 'en_cours';

        SELECT identifiant_statut_tournoi
          INTO v_statut_termine
          FROM ref_statut_tournoi
         WHERE code_statut = 'termine';

        UPDATE tournoi_club
           SET identifiant_statut_tournoi = v_statut_termine,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_tournoi = v_statut_en_cours
           AND date_fin < TRUNC(SYSDATE);
    END marquer_tournois_termines;

    PROCEDURE lancer_maintenance_quotidienne IS
    BEGIN
        cloturer_droits_media_expires;
        archiver_articles_non_traites;
        expirer_catalogue_boutique;
        clore_adhesions_expirees;
        marquer_tournois_termines;
        COMMIT;
    END lancer_maintenance_quotidienne;
END pkg_maintenance_site;
/

BEGIN
    pkg_schema_migration.enregistrer(
        '2.0.5',
        'Business views and PL SQL API for portal admin and maintenance',
        'schema'
    );
END;
/
