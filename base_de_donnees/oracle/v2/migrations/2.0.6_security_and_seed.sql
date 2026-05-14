PROMPT [2.0.6] Security and seed data

MERGE INTO ref_role_compte cible
USING (
    SELECT 'membre' AS code_role, 'Membre connecte' AS libelle_role, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'adherent', 'Adherent', 20 FROM dual
    UNION ALL SELECT 'redacteur', 'Redacteur', 30 FROM dual
    UNION ALL SELECT 'coach', 'Coach', 40 FROM dual
    UNION ALL SELECT 'tresorier', 'Tresorier', 50 FROM dual
    UNION ALL SELECT 'admin', 'Administrateur', 60 FROM dual
) source
ON (cible.code_role = source.code_role)
WHEN MATCHED THEN UPDATE SET cible.libelle_role = source.libelle_role, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_role, libelle_role, ordre_affichage)
VALUES (source.code_role, source.libelle_role, source.ordre_affichage);

MERGE INTO ref_statut_compte cible
USING (
    SELECT 'en_attente' AS code_statut, 'En attente' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'actif', 'Actif', 20 FROM dual
    UNION ALL SELECT 'suspendu', 'Suspendu', 30 FROM dual
    UNION ALL SELECT 'archive', 'Archive', 40 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_type_consentement cible
USING (
    SELECT 'politique_confidentialite' AS code_consentement, 'Politique de confidentialite' AS libelle_consentement, 'Interet legitime et information' AS base_legale, 24 AS duree_conservation_mois, 'Y' AS est_obligatoire FROM dual
    UNION ALL SELECT 'cookies_essentiels', 'Cookies essentiels', 'Interet legitime', 24, 'Y' FROM dual
    UNION ALL SELECT 'affichage_public_profil', 'Affichage public du profil', 'Consentement', 24, 'N' FROM dual
    UNION ALL SELECT 'droit_image', 'Droit a l''image et diffusion media', 'Consentement', 24, 'N' FROM dual
) source
ON (cible.code_consentement = source.code_consentement)
WHEN MATCHED THEN UPDATE SET cible.libelle_consentement = source.libelle_consentement, cible.base_legale = source.base_legale, cible.duree_conservation_mois = source.duree_conservation_mois, cible.est_obligatoire = source.est_obligatoire
WHEN NOT MATCHED THEN INSERT (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES (source.code_consentement, source.libelle_consentement, source.base_legale, source.duree_conservation_mois, source.est_obligatoire);

MERGE INTO ref_statut_article cible
USING (
    SELECT 'brouillon' AS code_statut, 'Brouillon' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'en_attente_validation', 'En attente de validation', 20 FROM dual
    UNION ALL SELECT 'publie', 'Publie', 30 FROM dual
    UNION ALL SELECT 'refuse', 'Refuse', 40 FROM dual
    UNION ALL SELECT 'archive', 'Archive', 50 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_decision_revision_article cible
USING (
    SELECT 'demander_modifications' AS code_decision, 'Demander des modifications' AS libelle_decision FROM dual
    UNION ALL SELECT 'approuver', 'Approuver' FROM dual
    UNION ALL SELECT 'refuser', 'Refuser' FROM dual
) source
ON (cible.code_decision = source.code_decision)
WHEN MATCHED THEN UPDATE SET cible.libelle_decision = source.libelle_decision
WHEN NOT MATCHED THEN INSERT (code_decision, libelle_decision)
VALUES (source.code_decision, source.libelle_decision);

MERGE INTO ref_type_media cible
USING (
    SELECT 'image' AS code_type_media, 'Image' AS libelle_type_media FROM dual
    UNION ALL SELECT 'video', 'Video' FROM dual
) source
ON (cible.code_type_media = source.code_type_media)
WHEN MATCHED THEN UPDATE SET cible.libelle_type_media = source.libelle_type_media
WHEN NOT MATCHED THEN INSERT (code_type_media, libelle_type_media)
VALUES (source.code_type_media, source.libelle_type_media);

MERGE INTO ref_mode_stockage_media cible
USING (
    SELECT 'blob_base' AS code_mode_stockage, 'Stockage Oracle' AS libelle_mode_stockage FROM dual
    UNION ALL SELECT 'uri_externe', 'Reference externe' FROM dual
) source
ON (cible.code_mode_stockage = source.code_mode_stockage)
WHEN MATCHED THEN UPDATE SET cible.libelle_mode_stockage = source.libelle_mode_stockage
WHEN NOT MATCHED THEN INSERT (code_mode_stockage, libelle_mode_stockage)
VALUES (source.code_mode_stockage, source.libelle_mode_stockage);

MERGE INTO ref_statut_media cible
USING (
    SELECT 'en_attente_validation' AS code_statut, 'En attente de validation' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'publie', 'Publie', 20 FROM dual
    UNION ALL SELECT 'refuse', 'Refuse', 30 FROM dual
    UNION ALL SELECT 'archive', 'Archive', 40 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_statut_droits_media cible
USING (
    SELECT 'en_attente' AS code_statut, 'En attente' AS libelle_statut FROM dual
    UNION ALL SELECT 'accorde', 'Accorde' FROM dual
    UNION ALL SELECT 'expire', 'Expire' FROM dual
    UNION ALL SELECT 'revoque', 'Revoque' FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut)
VALUES (source.code_statut, source.libelle_statut);

MERGE INTO ref_categorie_produit cible
USING (
    SELECT 'textile' AS code_categorie, 'Textile' AS libelle_categorie, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'accessoire', 'Accessoire', 20 FROM dual
    UNION ALL SELECT 'materiel', 'Materiel', 30 FROM dual
    UNION ALL SELECT 'autre', 'Autre', 40 FROM dual
) source
ON (cible.code_categorie = source.code_categorie)
WHEN MATCHED THEN UPDATE SET cible.libelle_categorie = source.libelle_categorie, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_categorie, libelle_categorie, ordre_affichage)
VALUES (source.code_categorie, source.libelle_categorie, source.ordre_affichage);

MERGE INTO ref_statut_produit cible
USING (
    SELECT 'brouillon' AS code_statut, 'Brouillon' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'actif', 'Actif', 20 FROM dual
    UNION ALL SELECT 'indisponible', 'Indisponible', 30 FROM dual
    UNION ALL SELECT 'expire', 'Expire', 40 FROM dual
    UNION ALL SELECT 'archive', 'Archive', 50 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_statut_commande cible
USING (
    SELECT 'en_attente' AS code_statut, 'En attente' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'paye', 'Paye', 20 FROM dual
    UNION ALL SELECT 'annule', 'Annule', 30 FROM dual
    UNION ALL SELECT 'rembourse', 'Rembourse', 40 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_type_adhesion cible
USING (
    SELECT 'adulte' AS code_type_adhesion, 'Adulte' AS libelle_type_adhesion, 120 AS montant_base FROM dual
    UNION ALL SELECT 'jeune', 'Jeune', 80 FROM dual
    UNION ALL SELECT 'etudiant', 'Etudiant', 95 FROM dual
    UNION ALL SELECT 'soutien', 'Soutien', 180 FROM dual
) source
ON (cible.code_type_adhesion = source.code_type_adhesion)
WHEN MATCHED THEN UPDATE SET cible.libelle_type_adhesion = source.libelle_type_adhesion, cible.montant_base = source.montant_base
WHEN NOT MATCHED THEN INSERT (code_type_adhesion, libelle_type_adhesion, montant_base)
VALUES (source.code_type_adhesion, source.libelle_type_adhesion, source.montant_base);

MERGE INTO ref_statut_adhesion cible
USING (
    SELECT 'brouillon' AS code_statut, 'Brouillon' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'en_attente_paiement', 'En attente de paiement', 20 FROM dual
    UNION ALL SELECT 'active', 'Active', 30 FROM dual
    UNION ALL SELECT 'expiree', 'Expiree', 40 FROM dual
    UNION ALL SELECT 'annulee', 'Annulee', 50 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_type_activite cible
USING (
    SELECT 'cours' AS code_type_activite, 'Cours' AS libelle_type_activite FROM dual
    UNION ALL SELECT 'entrainement', 'Entrainement' FROM dual
    UNION ALL SELECT 'analyse', 'Analyse de parties' FROM dual
    UNION ALL SELECT 'competition', 'Competition' FROM dual
    UNION ALL SELECT 'animation', 'Animation club' FROM dual
) source
ON (cible.code_type_activite = source.code_type_activite)
WHEN MATCHED THEN UPDATE SET cible.libelle_type_activite = source.libelle_type_activite
WHEN NOT MATCHED THEN INSERT (code_type_activite, libelle_type_activite)
VALUES (source.code_type_activite, source.libelle_type_activite);

MERGE INTO ref_niveau_joueur cible
USING (
    SELECT 'debutant' AS code_niveau, 'Debutant' AS libelle_niveau, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'intermediaire', 'Intermediaire', 20 FROM dual
    UNION ALL SELECT 'avance', 'Avance', 30 FROM dual
    UNION ALL SELECT 'expert', 'Expert', 40 FROM dual
) source
ON (cible.code_niveau = source.code_niveau)
WHEN MATCHED THEN UPDATE SET cible.libelle_niveau = source.libelle_niveau, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_niveau, libelle_niveau, ordre_affichage)
VALUES (source.code_niveau, source.libelle_niveau, source.ordre_affichage);

MERGE INTO ref_statut_inscription_session cible
USING (
    SELECT 'inscrit' AS code_statut, 'Inscrit' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'liste_attente', 'Liste d''attente', 20 FROM dual
    UNION ALL SELECT 'present', 'Present', 30 FROM dual
    UNION ALL SELECT 'absent', 'Absent', 40 FROM dual
    UNION ALL SELECT 'annule', 'Annule', 50 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_statut_tournoi cible
USING (
    SELECT 'brouillon' AS code_statut, 'Brouillon' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'ouvert_inscription', 'Ouvert aux inscriptions', 20 FROM dual
    UNION ALL SELECT 'en_cours', 'En cours', 30 FROM dual
    UNION ALL SELECT 'termine', 'Termine', 40 FROM dual
    UNION ALL SELECT 'annule', 'Annule', 50 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_statut_inscription_tournoi cible
USING (
    SELECT 'inscrit' AS code_statut, 'Inscrit' AS libelle_statut, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'confirme', 'Confirme', 20 FROM dual
    UNION ALL SELECT 'retire', 'Retire', 30 FROM dual
    UNION ALL SELECT 'forfait', 'Forfait', 40 FROM dual
) source
ON (cible.code_statut = source.code_statut)
WHEN MATCHED THEN UPDATE SET cible.libelle_statut = source.libelle_statut, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_statut, libelle_statut, ordre_affichage)
VALUES (source.code_statut, source.libelle_statut, source.ordre_affichage);

MERGE INTO ref_type_classement cible
USING (
    SELECT 'fide' AS code_type_classement, 'Classement FIDE' AS libelle_type_classement, 10 AS ordre_affichage FROM dual
    UNION ALL SELECT 'rapid', 'Classement rapide', 20 FROM dual
    UNION ALL SELECT 'blitz', 'Classement blitz', 30 FROM dual
    UNION ALL SELECT 'bullet', 'Classement bullet', 40 FROM dual
) source
ON (cible.code_type_classement = source.code_type_classement)
WHEN MATCHED THEN UPDATE SET cible.libelle_type_classement = source.libelle_type_classement, cible.ordre_affichage = source.ordre_affichage
WHEN NOT MATCHED THEN INSERT (code_type_classement, libelle_type_classement, ordre_affichage)
VALUES (source.code_type_classement, source.libelle_type_classement, source.ordre_affichage);

MERGE INTO type_document_juridique cible
USING (
    SELECT 'politique_confidentialite' AS code_document, 'Politique de confidentialite' AS libelle_document FROM dual
    UNION ALL SELECT 'mentions_legales', 'Mentions legales' FROM dual
    UNION ALL SELECT 'charte_publication', 'Charte de publication' FROM dual
) source
ON (cible.code_document = source.code_document)
WHEN MATCHED THEN UPDATE SET cible.libelle_document = source.libelle_document
WHEN NOT MATCHED THEN INSERT (code_document, libelle_document)
VALUES (source.code_document, source.libelle_document);

MERGE INTO document_juridique_version cible
USING (
    SELECT identifiant_type_document_juridique, 'v1.0' AS version_document, 'Politique de confidentialite v1.0' AS titre_document, 'Version initiale a personnaliser.' AS contenu_document, 'Y' AS est_version_active
      FROM type_document_juridique
     WHERE code_document = 'politique_confidentialite'
    UNION ALL
    SELECT identifiant_type_document_juridique, 'v1.0', 'Mentions legales v1.0', 'Version initiale a personnaliser.', 'Y'
      FROM type_document_juridique
     WHERE code_document = 'mentions_legales'
    UNION ALL
    SELECT identifiant_type_document_juridique, 'v1.0', 'Charte de publication v1.0', 'Version initiale a personnaliser.', 'Y'
      FROM type_document_juridique
     WHERE code_document = 'charte_publication'
) source
ON (
    cible.identifiant_type_document_juridique = source.identifiant_type_document_juridique
    AND cible.version_document = source.version_document
)
WHEN MATCHED THEN UPDATE SET
    cible.titre_document = source.titre_document,
    cible.contenu_document = source.contenu_document,
    cible.est_version_active = source.est_version_active
WHEN NOT MATCHED THEN INSERT (
    identifiant_type_document_juridique,
    version_document,
    titre_document,
    contenu_document,
    est_version_active
) VALUES (
    source.identifiant_type_document_juridique,
    source.version_document,
    source.titre_document,
    source.contenu_document,
    source.est_version_active
);

MERGE INTO parametre_application cible
USING (
    SELECT 'theme_par_defaut' AS cle_parametre, 'clair' AS valeur_parametre, 'texte' AS type_parametre, 'Theme par defaut du site' AS description_parametre FROM dual
    UNION ALL SELECT 'moderation_article_automatique', 'N', 'booleen', 'Doit rester a N tant que la moderation humaine est obligatoire' FROM dual
    UNION ALL SELECT 'moderation_media_automatique', 'N', 'booleen', 'Doit rester a N tant que la moderation humaine est obligatoire' FROM dual
    UNION ALL SELECT 'devise_boutique_par_defaut', 'EUR', 'texte', 'Devise par defaut de la boutique' FROM dual
) source
ON (cible.cle_parametre = source.cle_parametre)
WHEN MATCHED THEN UPDATE SET
    cible.valeur_parametre = source.valeur_parametre,
    cible.type_parametre = source.type_parametre,
    cible.description_parametre = source.description_parametre
WHEN NOT MATCHED THEN INSERT (
    cle_parametre,
    valeur_parametre,
    type_parametre,
    description_parametre
) VALUES (
    source.cle_parametre,
    source.valeur_parametre,
    source.type_parametre,
    source.description_parametre
);

BEGIN
    EXECUTE IMMEDIATE 'CREATE ROLE rl_site_app';
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -1921 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'CREATE ROLE rl_site_admin_ops';
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -1921 THEN
            RAISE;
        END IF;
END;
/

BEGIN
    EXECUTE IMMEDIATE 'CREATE ROLE rl_site_audit';
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -1921 THEN
            RAISE;
        END IF;
END;
/

GRANT SELECT ON vw_catalogue_boutique_public TO rl_site_app;
GRANT SELECT ON vw_calendrier_activites_public TO rl_site_app;
GRANT SELECT ON vw_ressources_media_pretes_publication TO rl_site_app;
GRANT EXECUTE ON pkg_site_portail TO rl_site_app;

GRANT SELECT ON vw_admin_comptes TO rl_site_admin_ops;
GRANT SELECT ON vw_admin_articles TO rl_site_admin_ops;
GRANT SELECT ON vw_admin_medias TO rl_site_admin_ops;
GRANT SELECT ON vw_adhesions_actives TO rl_site_admin_ops;
GRANT EXECUTE ON pkg_site_portail TO rl_site_admin_ops;
GRANT EXECUTE ON pkg_site_admin TO rl_site_admin_ops;
GRANT EXECUTE ON pkg_maintenance_site TO rl_site_admin_ops;

GRANT SELECT ON schema_migration TO rl_site_audit;
GRANT SELECT ON journal_audit_administration TO rl_site_audit;
GRANT SELECT ON journal_connexion TO rl_site_audit;
GRANT SELECT ON vw_admin_articles TO rl_site_audit;
GRANT SELECT ON vw_admin_medias TO rl_site_audit;
GRANT SELECT ON vw_admin_comptes TO rl_site_audit;

BEGIN
    pkg_schema_migration.enregistrer(
        '2.0.6',
        'Seed data, legal defaults and security roles',
        'seed'
    );
END;
/

COMMIT;
