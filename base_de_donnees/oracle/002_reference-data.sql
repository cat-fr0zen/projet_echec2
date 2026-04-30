-- Reference data for the Oracle schema.

INSERT INTO account_role (role_code, role_label) VALUES ('member', 'Membre');
INSERT INTO account_role (role_code, role_label) VALUES ('editor', 'Rédacteur');
INSERT INTO account_role (role_code, role_label) VALUES ('admin', 'Administrateur');

INSERT INTO account_status (status_code, status_label) VALUES ('pending', 'En attente');
INSERT INTO account_status (status_code, status_label) VALUES ('active', 'Actif');
INSERT INTO account_status (status_code, status_label) VALUES ('suspended', 'Suspendu');
INSERT INTO account_status (status_code, status_label) VALUES ('deleted', 'Supprimé');

INSERT INTO publication_status (status_code, status_label) VALUES ('draft', 'Brouillon');
INSERT INTO publication_status (status_code, status_label) VALUES ('pending_review', 'En attente de validation');
INSERT INTO publication_status (status_code, status_label) VALUES ('approved', 'Approuvé');
INSERT INTO publication_status (status_code, status_label) VALUES ('rejected', 'Refusé');
INSERT INTO publication_status (status_code, status_label) VALUES ('published', 'Publié');
INSERT INTO publication_status (status_code, status_label) VALUES ('archived', 'Archivé');

INSERT INTO review_decision_type (decision_code, decision_label) VALUES ('request_changes', 'Demander des modifications');
INSERT INTO review_decision_type (decision_code, decision_label) VALUES ('approve', 'Approuver');
INSERT INTO review_decision_type (decision_code, decision_label) VALUES ('reject', 'Refuser');

INSERT INTO consent_type (consent_code, consent_label, legal_basis, retention_months, is_required)
VALUES ('privacy_policy', 'Politique de confidentialité', 'Obligation d''information', 24, 'Y');
INSERT INTO consent_type (consent_code, consent_label, legal_basis, retention_months, is_required)
VALUES ('site_terms', 'Conditions d''utilisation', 'Intérêt légitime', 24, 'Y');
INSERT INTO consent_type (consent_code, consent_label, legal_basis, retention_months, is_required)
VALUES ('essential_cookies', 'Cookies essentiels', 'Fonctionnement du service', 24, 'Y');
INSERT INTO consent_type (consent_code, consent_label, legal_basis, retention_months, is_required)
VALUES ('theme_preference', 'Préférence de thème', 'Préférence utilisateur', 24, 'N');
INSERT INTO consent_type (consent_code, consent_label, legal_basis, retention_months, is_required)
VALUES ('media_publication', 'Autorisation de diffusion d''image ou de vidéo', 'Consentement', 36, 'N');

INSERT INTO media_type (media_type_code, media_type_label) VALUES ('image', 'Image');
INSERT INTO media_type (media_type_code, media_type_label) VALUES ('video', 'Vidéo');

INSERT INTO media_usage_type (usage_code, usage_label) VALUES ('article_cover', 'Illustration principale d''article');
INSERT INTO media_usage_type (usage_code, usage_label) VALUES ('article_gallery', 'Galerie d''article');
INSERT INTO media_usage_type (usage_code, usage_label) VALUES ('album_gallery', 'Galerie de médiathèque');
INSERT INTO media_usage_type (usage_code, usage_label) VALUES ('product_primary', 'Visuel principal de produit');
INSERT INTO media_usage_type (usage_code, usage_label) VALUES ('product_gallery', 'Galerie de produit');

INSERT INTO media_rights_status (status_code, status_label) VALUES ('pending', 'En attente de vérification');
INSERT INTO media_rights_status (status_code, status_label) VALUES ('granted', 'Droits accordés');
INSERT INTO media_rights_status (status_code, status_label) VALUES ('expired', 'Droits expirés');
INSERT INTO media_rights_status (status_code, status_label) VALUES ('revoked', 'Droits révoqués');

INSERT INTO media_storage_mode (storage_mode_code, storage_mode_label) VALUES ('database_blob', 'Stockage dans Oracle');
INSERT INTO media_storage_mode (storage_mode_code, storage_mode_label) VALUES ('external_uri', 'Stockage externe référencé');

INSERT INTO product_category (category_code, category_label) VALUES ('textile', 'Textile');
INSERT INTO product_category (category_code, category_label) VALUES ('accessoire', 'Accessoire');
INSERT INTO product_category (category_code, category_label) VALUES ('materiel', 'Matériel');
INSERT INTO product_category (category_code, category_label) VALUES ('autre', 'Autre');

INSERT INTO product_status (status_code, status_label) VALUES ('draft', 'Brouillon');
INSERT INTO product_status (status_code, status_label) VALUES ('active', 'Actif');
INSERT INTO product_status (status_code, status_label) VALUES ('unavailable', 'Indisponible');
INSERT INTO product_status (status_code, status_label) VALUES ('expired', 'Expiré');
INSERT INTO product_status (status_code, status_label) VALUES ('archived', 'Archivé');

INSERT INTO order_status (status_code, status_label) VALUES ('pending', 'En attente');
INSERT INTO order_status (status_code, status_label) VALUES ('paid', 'Payé');
INSERT INTO order_status (status_code, status_label) VALUES ('canceled', 'Annulé');
INSERT INTO order_status (status_code, status_label) VALUES ('refunded', 'Remboursé');

COMMIT;
