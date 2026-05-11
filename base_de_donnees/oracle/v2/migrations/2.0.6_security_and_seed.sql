PROMPT [2.0.6] Security and seed data

INSERT INTO ref_role_compte (code_role, libelle_role, ordre_affichage) VALUES ('membre', 'Membre connecte', 10);
INSERT INTO ref_role_compte (code_role, libelle_role, ordre_affichage) VALUES ('adherent', 'Adherent', 20);
INSERT INTO ref_role_compte (code_role, libelle_role, ordre_affichage) VALUES ('redacteur', 'Redacteur', 30);
INSERT INTO ref_role_compte (code_role, libelle_role, ordre_affichage) VALUES ('coach', 'Coach', 40);
INSERT INTO ref_role_compte (code_role, libelle_role, ordre_affichage) VALUES ('tresorier', 'Tresorier', 50);
INSERT INTO ref_role_compte (code_role, libelle_role, ordre_affichage) VALUES ('admin', 'Administrateur', 60);

INSERT INTO ref_statut_compte (code_statut, libelle_statut, ordre_affichage) VALUES ('en_attente', 'En attente', 10);
INSERT INTO ref_statut_compte (code_statut, libelle_statut, ordre_affichage) VALUES ('actif', 'Actif', 20);
INSERT INTO ref_statut_compte (code_statut, libelle_statut, ordre_affichage) VALUES ('suspendu', 'Suspendu', 30);
INSERT INTO ref_statut_compte (code_statut, libelle_statut, ordre_affichage) VALUES ('archive', 'Archive', 40);

INSERT INTO ref_type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('politique_confidentialite', 'Politique de confidentialite', 'Interet legitime et information', 24, 'Y');
INSERT INTO ref_type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('cookies_essentiels', 'Cookies essentiels', 'Interet legitime', 24, 'Y');
INSERT INTO ref_type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('affichage_public_profil', 'Affichage public du profil', 'Consentement', 24, 'N');
INSERT INTO ref_type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('droit_image', 'Droit a l''image et diffusion media', 'Consentement', 24, 'N');

INSERT INTO ref_statut_article (code_statut, libelle_statut, ordre_affichage) VALUES ('brouillon', 'Brouillon', 10);
INSERT INTO ref_statut_article (code_statut, libelle_statut, ordre_affichage) VALUES ('en_attente_validation', 'En attente de validation', 20);
INSERT INTO ref_statut_article (code_statut, libelle_statut, ordre_affichage) VALUES ('publie', 'Publie', 30);
INSERT INTO ref_statut_article (code_statut, libelle_statut, ordre_affichage) VALUES ('refuse', 'Refuse', 40);
INSERT INTO ref_statut_article (code_statut, libelle_statut, ordre_affichage) VALUES ('archive', 'Archive', 50);

INSERT INTO ref_decision_revision_article (code_decision, libelle_decision) VALUES ('demander_modifications', 'Demander des modifications');
INSERT INTO ref_decision_revision_article (code_decision, libelle_decision) VALUES ('approuver', 'Approuver');
INSERT INTO ref_decision_revision_article (code_decision, libelle_decision) VALUES ('refuser', 'Refuser');

INSERT INTO ref_type_media (code_type_media, libelle_type_media) VALUES ('image', 'Image');
INSERT INTO ref_type_media (code_type_media, libelle_type_media) VALUES ('video', 'Video');

INSERT INTO ref_mode_stockage_media (code_mode_stockage, libelle_mode_stockage) VALUES ('blob_base', 'Stockage Oracle');
INSERT INTO ref_mode_stockage_media (code_mode_stockage, libelle_mode_stockage) VALUES ('uri_externe', 'Reference externe');

INSERT INTO ref_statut_media (code_statut, libelle_statut, ordre_affichage) VALUES ('en_attente_validation', 'En attente de validation', 10);
INSERT INTO ref_statut_media (code_statut, libelle_statut, ordre_affichage) VALUES ('publie', 'Publie', 20);
INSERT INTO ref_statut_media (code_statut, libelle_statut, ordre_affichage) VALUES ('refuse', 'Refuse', 30);
INSERT INTO ref_statut_media (code_statut, libelle_statut, ordre_affichage) VALUES ('archive', 'Archive', 40);

INSERT INTO ref_statut_droits_media (code_statut, libelle_statut) VALUES ('en_attente', 'En attente');
INSERT INTO ref_statut_droits_media (code_statut, libelle_statut) VALUES ('accorde', 'Accorde');
INSERT INTO ref_statut_droits_media (code_statut, libelle_statut) VALUES ('expire', 'Expire');
INSERT INTO ref_statut_droits_media (code_statut, libelle_statut) VALUES ('revoque', 'Revoque');

INSERT INTO ref_categorie_produit (code_categorie, libelle_categorie, ordre_affichage) VALUES ('textile', 'Textile', 10);
INSERT INTO ref_categorie_produit (code_categorie, libelle_categorie, ordre_affichage) VALUES ('accessoire', 'Accessoire', 20);
INSERT INTO ref_categorie_produit (code_categorie, libelle_categorie, ordre_affichage) VALUES ('materiel', 'Materiel', 30);
INSERT INTO ref_categorie_produit (code_categorie, libelle_categorie, ordre_affichage) VALUES ('autre', 'Autre', 40);

INSERT INTO ref_statut_produit (code_statut, libelle_statut, ordre_affichage) VALUES ('brouillon', 'Brouillon', 10);
INSERT INTO ref_statut_produit (code_statut, libelle_statut, ordre_affichage) VALUES ('actif', 'Actif', 20);
INSERT INTO ref_statut_produit (code_statut, libelle_statut, ordre_affichage) VALUES ('indisponible', 'Indisponible', 30);
INSERT INTO ref_statut_produit (code_statut, libelle_statut, ordre_affichage) VALUES ('expire', 'Expire', 40);
INSERT INTO ref_statut_produit (code_statut, libelle_statut, ordre_affichage) VALUES ('archive', 'Archive', 50);

INSERT INTO ref_statut_commande (code_statut, libelle_statut, ordre_affichage) VALUES ('en_attente', 'En attente', 10);
INSERT INTO ref_statut_commande (code_statut, libelle_statut, ordre_affichage) VALUES ('paye', 'Paye', 20);
INSERT INTO ref_statut_commande (code_statut, libelle_statut, ordre_affichage) VALUES ('annule', 'Annule', 30);
INSERT INTO ref_statut_commande (code_statut, libelle_statut, ordre_affichage) VALUES ('rembourse', 'Rembourse', 40);

INSERT INTO ref_type_adhesion (code_type_adhesion, libelle_type_adhesion, montant_base) VALUES ('adulte', 'Adulte', 120);
INSERT INTO ref_type_adhesion (code_type_adhesion, libelle_type_adhesion, montant_base) VALUES ('jeune', 'Jeune', 80);
INSERT INTO ref_type_adhesion (code_type_adhesion, libelle_type_adhesion, montant_base) VALUES ('etudiant', 'Etudiant', 95);
INSERT INTO ref_type_adhesion (code_type_adhesion, libelle_type_adhesion, montant_base) VALUES ('soutien', 'Soutien', 180);

INSERT INTO ref_statut_adhesion (code_statut, libelle_statut, ordre_affichage) VALUES ('brouillon', 'Brouillon', 10);
INSERT INTO ref_statut_adhesion (code_statut, libelle_statut, ordre_affichage) VALUES ('en_attente_paiement', 'En attente de paiement', 20);
INSERT INTO ref_statut_adhesion (code_statut, libelle_statut, ordre_affichage) VALUES ('active', 'Active', 30);
INSERT INTO ref_statut_adhesion (code_statut, libelle_statut, ordre_affichage) VALUES ('expiree', 'Expiree', 40);
INSERT INTO ref_statut_adhesion (code_statut, libelle_statut, ordre_affichage) VALUES ('annulee', 'Annulee', 50);

INSERT INTO ref_type_activite (code_type_activite, libelle_type_activite) VALUES ('cours', 'Cours');
INSERT INTO ref_type_activite (code_type_activite, libelle_type_activite) VALUES ('entrainement', 'Entrainement');
INSERT INTO ref_type_activite (code_type_activite, libelle_type_activite) VALUES ('analyse', 'Analyse de parties');
INSERT INTO ref_type_activite (code_type_activite, libelle_type_activite) VALUES ('competition', 'Competition');
INSERT INTO ref_type_activite (code_type_activite, libelle_type_activite) VALUES ('animation', 'Animation club');

INSERT INTO ref_niveau_joueur (code_niveau, libelle_niveau, ordre_affichage) VALUES ('debutant', 'Debutant', 10);
INSERT INTO ref_niveau_joueur (code_niveau, libelle_niveau, ordre_affichage) VALUES ('intermediaire', 'Intermediaire', 20);
INSERT INTO ref_niveau_joueur (code_niveau, libelle_niveau, ordre_affichage) VALUES ('avance', 'Avance', 30);
INSERT INTO ref_niveau_joueur (code_niveau, libelle_niveau, ordre_affichage) VALUES ('expert', 'Expert', 40);

INSERT INTO ref_statut_inscription_session (code_statut, libelle_statut, ordre_affichage) VALUES ('inscrit', 'Inscrit', 10);
INSERT INTO ref_statut_inscription_session (code_statut, libelle_statut, ordre_affichage) VALUES ('liste_attente', 'Liste d''attente', 20);
INSERT INTO ref_statut_inscription_session (code_statut, libelle_statut, ordre_affichage) VALUES ('present', 'Present', 30);
INSERT INTO ref_statut_inscription_session (code_statut, libelle_statut, ordre_affichage) VALUES ('absent', 'Absent', 40);
INSERT INTO ref_statut_inscription_session (code_statut, libelle_statut, ordre_affichage) VALUES ('annule', 'Annule', 50);

INSERT INTO ref_statut_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('brouillon', 'Brouillon', 10);
INSERT INTO ref_statut_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('ouvert_inscription', 'Ouvert aux inscriptions', 20);
INSERT INTO ref_statut_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('en_cours', 'En cours', 30);
INSERT INTO ref_statut_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('termine', 'Termine', 40);
INSERT INTO ref_statut_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('annule', 'Annule', 50);

INSERT INTO ref_statut_inscription_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('inscrit', 'Inscrit', 10);
INSERT INTO ref_statut_inscription_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('confirme', 'Confirme', 20);
INSERT INTO ref_statut_inscription_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('retire', 'Retire', 30);
INSERT INTO ref_statut_inscription_tournoi (code_statut, libelle_statut, ordre_affichage) VALUES ('forfait', 'Forfait', 40);

INSERT INTO ref_type_classement (code_type_classement, libelle_type_classement, ordre_affichage) VALUES ('fide', 'Classement FIDE', 10);
INSERT INTO ref_type_classement (code_type_classement, libelle_type_classement, ordre_affichage) VALUES ('rapid', 'Classement rapide', 20);
INSERT INTO ref_type_classement (code_type_classement, libelle_type_classement, ordre_affichage) VALUES ('blitz', 'Classement blitz', 30);
INSERT INTO ref_type_classement (code_type_classement, libelle_type_classement, ordre_affichage) VALUES ('bullet', 'Classement bullet', 40);

INSERT INTO type_document_juridique (code_document, libelle_document) VALUES ('politique_confidentialite', 'Politique de confidentialite');
INSERT INTO type_document_juridique (code_document, libelle_document) VALUES ('mentions_legales', 'Mentions legales');
INSERT INTO type_document_juridique (code_document, libelle_document) VALUES ('charte_publication', 'Charte de publication');

INSERT INTO document_juridique_version (
    identifiant_type_document_juridique,
    version_document,
    titre_document,
    contenu_document,
    est_version_active
)
SELECT identifiant_type_document_juridique, 'v1.0', 'Politique de confidentialite v1.0', 'Version initiale a personnaliser.', 'Y'
  FROM type_document_juridique
 WHERE code_document = 'politique_confidentialite';

INSERT INTO document_juridique_version (
    identifiant_type_document_juridique,
    version_document,
    titre_document,
    contenu_document,
    est_version_active
)
SELECT identifiant_type_document_juridique, 'v1.0', 'Mentions legales v1.0', 'Version initiale a personnaliser.', 'Y'
  FROM type_document_juridique
 WHERE code_document = 'mentions_legales';

INSERT INTO document_juridique_version (
    identifiant_type_document_juridique,
    version_document,
    titre_document,
    contenu_document,
    est_version_active
)
SELECT identifiant_type_document_juridique, 'v1.0', 'Charte de publication v1.0', 'Version initiale a personnaliser.', 'Y'
  FROM type_document_juridique
 WHERE code_document = 'charte_publication';

INSERT INTO parametre_application (cle_parametre, valeur_parametre, type_parametre, description_parametre)
VALUES ('theme_par_defaut', 'clair', 'texte', 'Theme par defaut du site');
INSERT INTO parametre_application (cle_parametre, valeur_parametre, type_parametre, description_parametre)
VALUES ('moderation_article_automatique', 'N', 'booleen', 'Doit rester a N tant que la moderation humaine est obligatoire');
INSERT INTO parametre_application (cle_parametre, valeur_parametre, type_parametre, description_parametre)
VALUES ('moderation_media_automatique', 'N', 'booleen', 'Doit rester a N tant que la moderation humaine est obligatoire');
INSERT INTO parametre_application (cle_parametre, valeur_parametre, type_parametre, description_parametre)
VALUES ('devise_boutique_par_defaut', 'EUR', 'texte', 'Devise par defaut de la boutique');

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
