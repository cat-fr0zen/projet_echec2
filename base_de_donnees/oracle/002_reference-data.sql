-- Reference data for the Oracle schema.

INSERT INTO role_compte (code_role, libelle_role) VALUES ('membre', 'Membre');
INSERT INTO role_compte (code_role, libelle_role) VALUES ('redacteur', 'Rédacteur');
INSERT INTO role_compte (code_role, libelle_role) VALUES ('admin', 'Administrateur');

INSERT INTO statut_compte (code_statut, libelle_statut) VALUES ('en_attente', 'En attente');
INSERT INTO statut_compte (code_statut, libelle_statut) VALUES ('actif', 'Actif');
INSERT INTO statut_compte (code_statut, libelle_statut) VALUES ('suspendu', 'Suspendu');
INSERT INTO statut_compte (code_statut, libelle_statut) VALUES ('supprime', 'Supprimé');

INSERT INTO statut_publication (code_statut, libelle_statut) VALUES ('brouillon', 'Brouillon');
INSERT INTO statut_publication (code_statut, libelle_statut) VALUES ('en_attente_validation', 'En attente de validation');
INSERT INTO statut_publication (code_statut, libelle_statut) VALUES ('approuve', 'Approuvé');
INSERT INTO statut_publication (code_statut, libelle_statut) VALUES ('refuse', 'Refusé');
INSERT INTO statut_publication (code_statut, libelle_statut) VALUES ('publie', 'Publié');
INSERT INTO statut_publication (code_statut, libelle_statut) VALUES ('archive', 'Archivé');

INSERT INTO type_decision_revision (code_decision, libelle_decision) VALUES ('demander_modifications', 'Demander des modifications');
INSERT INTO type_decision_revision (code_decision, libelle_decision) VALUES ('approuver', 'Approuver');
INSERT INTO type_decision_revision (code_decision, libelle_decision) VALUES ('refuser', 'Refuser');

INSERT INTO type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('politique_confidentialite', 'Politique de confidentialité', 'Obligation d''information', 24, 'Y');
INSERT INTO type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('conditions_utilisation', 'Conditions d''utilisation', 'Intérêt légitime', 24, 'Y');
INSERT INTO type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('cookies_essentiels', 'Cookies essentiels', 'Fonctionnement du service', 24, 'Y');
INSERT INTO type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('preference_theme', 'Préférence de thème', 'Préférence utilisateur', 24, 'N');
INSERT INTO type_consentement (code_consentement, libelle_consentement, base_legale, duree_conservation_mois, est_obligatoire)
VALUES ('publication_media', 'Autorisation de diffusion d''image ou de vidéo', 'Consentement', 36, 'N');

INSERT INTO type_media (code_type_media, libelle_type_media) VALUES ('image', 'Image');
INSERT INTO type_media (code_type_media, libelle_type_media) VALUES ('video', 'Vidéo');

INSERT INTO type_usage_media (code_usage, libelle_usage) VALUES ('couverture_article', 'Illustration principale d''article');
INSERT INTO type_usage_media (code_usage, libelle_usage) VALUES ('galerie_article', 'Galerie d''article');
INSERT INTO type_usage_media (code_usage, libelle_usage) VALUES ('galerie_album', 'Galerie de médiathèque');
INSERT INTO type_usage_media (code_usage, libelle_usage) VALUES ('produit_principal', 'Visuel principal de produit');
INSERT INTO type_usage_media (code_usage, libelle_usage) VALUES ('galerie_produit', 'Galerie de produit');

INSERT INTO statut_droits_media (code_statut, libelle_statut) VALUES ('en_attente', 'En attente de vérification');
INSERT INTO statut_droits_media (code_statut, libelle_statut) VALUES ('accorde', 'Droits accordés');
INSERT INTO statut_droits_media (code_statut, libelle_statut) VALUES ('expire', 'Droits expirés');
INSERT INTO statut_droits_media (code_statut, libelle_statut) VALUES ('revoque', 'Droits révoqués');

INSERT INTO mode_stockage_media (code_mode_stockage, libelle_mode_stockage) VALUES ('blob_base', 'Stockage dans Oracle');
INSERT INTO mode_stockage_media (code_mode_stockage, libelle_mode_stockage) VALUES ('uri_externe', 'Stockage externe référencé');

INSERT INTO categorie_produit (code_categorie, libelle_categorie) VALUES ('textile', 'Textile');
INSERT INTO categorie_produit (code_categorie, libelle_categorie) VALUES ('accessoire', 'Accessoire');
INSERT INTO categorie_produit (code_categorie, libelle_categorie) VALUES ('materiel', 'Matériel');
INSERT INTO categorie_produit (code_categorie, libelle_categorie) VALUES ('autre', 'Autre');

INSERT INTO statut_produit (code_statut, libelle_statut) VALUES ('brouillon', 'Brouillon');
INSERT INTO statut_produit (code_statut, libelle_statut) VALUES ('actif', 'Actif');
INSERT INTO statut_produit (code_statut, libelle_statut) VALUES ('indisponible', 'Indisponible');
INSERT INTO statut_produit (code_statut, libelle_statut) VALUES ('expire', 'Expiré');
INSERT INTO statut_produit (code_statut, libelle_statut) VALUES ('archive', 'Archivé');

INSERT INTO statut_commande (code_statut, libelle_statut) VALUES ('en_attente', 'En attente');
INSERT INTO statut_commande (code_statut, libelle_statut) VALUES ('paye', 'Payé');
INSERT INTO statut_commande (code_statut, libelle_statut) VALUES ('annule', 'Annulé');
INSERT INTO statut_commande (code_statut, libelle_statut) VALUES ('rembourse', 'Remboursé');

COMMIT;



