/*
    Schema Oracle 19c - Cavaliers d'Herouville
    ------------------------------------------------------------
    Objectif:
    - remplacer le stockage metier JSON par une base relationnelle;
    - garder des cles publiques compatibles avec le site PHP;
    - cible officielle Oracle 19c pour PHP oci8_19.

    Execution:
    - a lancer sur un schema Oracle vide ou dedie;
    - le baseline et les scripts de verification sont dans ce dossier.
*/

CREATE TABLE schema_migration (
    version_schema      VARCHAR2(30)  NOT NULL,
    nom_migration       VARCHAR2(160) NOT NULL,
    categorie           VARCHAR2(60)  NOT NULL,
    applique_le         DATE DEFAULT SYSDATE NOT NULL,
    checksum            VARCHAR2(128),
    commentaire         VARCHAR2(1000),
    CONSTRAINT pk_schema_migration PRIMARY KEY (version_schema)
);

CREATE TABLE audit_changement_base (
    identifiant_changement VARCHAR2(80)  NOT NULL,
    categorie              VARCHAR2(60)  NOT NULL,
    operation              VARCHAR2(20)  NOT NULL,
    objet_cible            VARCHAR2(120) NOT NULL,
    description            VARCHAR2(1000) NOT NULL,
    demandeur              VARCHAR2(120),
    applique_par           VARCHAR2(120),
    applique_le            DATE DEFAULT SYSDATE NOT NULL,
    verification           VARCHAR2(1000),
    rollback_prevu         VARCHAR2(1000),
    CONSTRAINT pk_audit_changement_base PRIMARY KEY (identifiant_changement),
    CONSTRAINT ck_audit_changement_operation CHECK (operation IN ('ADD', 'UPDATE', 'DELETE', 'SECURITY', 'MIGRATION'))
);

CREATE TABLE ref_role_compte (
    code_role       VARCHAR2(30) NOT NULL,
    libelle_role    VARCHAR2(80) NOT NULL,
    niveau_acces    NUMBER(3) DEFAULT 0 NOT NULL,
    CONSTRAINT pk_ref_role_compte PRIMARY KEY (code_role),
    CONSTRAINT ck_ref_role_compte_code CHECK (code_role IN ('connecte', 'adherent', 'admin'))
);

CREATE TABLE ref_statut_compte (
    code_statut     VARCHAR2(30) NOT NULL,
    libelle_statut  VARCHAR2(80) NOT NULL,
    CONSTRAINT pk_ref_statut_compte PRIMARY KEY (code_statut),
    CONSTRAINT ck_ref_statut_compte_code CHECK (code_statut IN ('actif', 'suspendu'))
);

CREATE TABLE ref_statut_adhesion (
    code_statut     VARCHAR2(30) NOT NULL,
    libelle_statut  VARCHAR2(80) NOT NULL,
    CONSTRAINT pk_ref_statut_adhesion PRIMARY KEY (code_statut),
    CONSTRAINT ck_ref_statut_adhesion_code CHECK (code_statut IN ('aucune', 'active'))
);

CREATE TABLE ref_statut_publication (
    code_statut     VARCHAR2(40) NOT NULL,
    libelle_statut  VARCHAR2(80) NOT NULL,
    CONSTRAINT pk_ref_statut_publication PRIMARY KEY (code_statut),
    CONSTRAINT ck_ref_statut_publication CHECK (code_statut IN ('en_attente_validation', 'publie', 'refuse'))
);

CREATE TABLE ref_type_media (
    code_type       VARCHAR2(20) NOT NULL,
    libelle_type    VARCHAR2(80) NOT NULL,
    CONSTRAINT pk_ref_type_media PRIMARY KEY (code_type),
    CONSTRAINT ck_ref_type_media CHECK (code_type IN ('photo', 'video'))
);

CREATE TABLE ref_statut_commande (
    code_statut     VARCHAR2(30) NOT NULL,
    libelle_statut  VARCHAR2(80) NOT NULL,
    CONSTRAINT pk_ref_statut_commande PRIMARY KEY (code_statut),
    CONSTRAINT ck_ref_statut_commande CHECK (code_statut IN ('en_attente', 'validee', 'annulee'))
);

CREATE TABLE ref_type_bloc_article (
    code_type       VARCHAR2(30) NOT NULL,
    libelle_type    VARCHAR2(80) NOT NULL,
    CONSTRAINT pk_ref_type_bloc_article PRIMARY KEY (code_type),
    CONSTRAINT ck_ref_type_bloc_article CHECK (code_type IN ('paragraphe', 'sous_titre', 'image', 'video'))
);

CREATE TABLE compte_membre (
    identifiant                 VARCHAR2(40)  NOT NULL,
    nom                         VARCHAR2(100) NOT NULL,
    prenom                      VARCHAR2(100) NOT NULL,
    date_naissance              VARCHAR2(10),
    courriel                    VARCHAR2(254) NOT NULL,
    courriel_normalise          VARCHAR2(254) NOT NULL,
    numero_licence_federale     VARCHAR2(30),
    mot_de_passe_hache          VARCHAR2(255) NOT NULL,
    description_profil          VARCHAR2(1200),
    pseudo_chess                VARCHAR2(50),
    code_role                   VARCHAR2(30) DEFAULT 'connecte' NOT NULL,
    code_statut_compte          VARCHAR2(30) DEFAULT 'actif' NOT NULL,
    code_statut_adhesion        VARCHAR2(30) DEFAULT 'aucune' NOT NULL,
    cree_le                     DATE DEFAULT SYSDATE NOT NULL,
    mis_a_jour_le               DATE,
    CONSTRAINT pk_compte_membre PRIMARY KEY (identifiant),
    CONSTRAINT uq_compte_membre_email UNIQUE (courriel_normalise),
    CONSTRAINT fk_compte_membre_role FOREIGN KEY (code_role) REFERENCES ref_role_compte (code_role),
    CONSTRAINT fk_compte_membre_statut FOREIGN KEY (code_statut_compte) REFERENCES ref_statut_compte (code_statut),
    CONSTRAINT fk_compte_membre_adhesion FOREIGN KEY (code_statut_adhesion) REFERENCES ref_statut_adhesion (code_statut),
    CONSTRAINT ck_compte_membre_email CHECK (INSTR(courriel_normalise, '@') > 1),
    CONSTRAINT ck_compte_membre_licence CHECK (numero_licence_federale IS NULL OR REGEXP_LIKE(numero_licence_federale, '^[A-Z0-9-]{3,30}$'))
);

CREATE UNIQUE INDEX uq_compte_membre_licence_ffe
    ON compte_membre (UPPER(numero_licence_federale));

CREATE TABLE article (
    identifiant             VARCHAR2(40)  NOT NULL,
    identifiant_auteur      VARCHAR2(40)  NOT NULL,
    nom_auteur              VARCHAR2(220) NOT NULL,
    auteur_affiche          VARCHAR2(120) NOT NULL,
    titre                   VARCHAR2(150) NOT NULL,
    resume                  VARCHAR2(500),
    contenu                 VARCHAR2(4000),
    code_statut             VARCHAR2(40) DEFAULT 'en_attente_validation' NOT NULL,
    cree_le                 DATE DEFAULT SYSDATE NOT NULL,
    mis_a_jour_le           DATE,
    CONSTRAINT pk_article PRIMARY KEY (identifiant),
    CONSTRAINT fk_article_auteur FOREIGN KEY (identifiant_auteur) REFERENCES compte_membre (identifiant),
    CONSTRAINT fk_article_statut FOREIGN KEY (code_statut) REFERENCES ref_statut_publication (code_statut)
);

CREATE INDEX ix_article_statut_date ON article (code_statut, cree_le);
CREATE INDEX ix_article_auteur_date ON article (identifiant_auteur, cree_le);

CREATE TABLE article_bloc (
    identifiant_bloc        VARCHAR2(50) NOT NULL,
    identifiant_article     VARCHAR2(40) NOT NULL,
    ordre_affichage         NUMBER(4) NOT NULL,
    code_type               VARCHAR2(30) NOT NULL,
    texte                   VARCHAR2(4000),
    chemin_public           VARCHAR2(500),
    type_mime               VARCHAR2(120),
    texte_alternatif        VARCHAR2(180),
    legende                 VARCHAR2(220),
    nom_fichier_original    VARCHAR2(255),
    taille_octets           NUMBER(12) DEFAULT 0 NOT NULL,
    CONSTRAINT pk_article_bloc PRIMARY KEY (identifiant_bloc),
    CONSTRAINT uq_article_bloc_ordre UNIQUE (identifiant_article, ordre_affichage),
    CONSTRAINT fk_article_bloc_article FOREIGN KEY (identifiant_article) REFERENCES article (identifiant) ON DELETE CASCADE,
    CONSTRAINT fk_article_bloc_type FOREIGN KEY (code_type) REFERENCES ref_type_bloc_article (code_type),
    CONSTRAINT ck_article_bloc_taille CHECK (taille_octets >= 0)
);

CREATE TABLE media_publication (
    identifiant             VARCHAR2(40)  NOT NULL,
    identifiant_auteur      VARCHAR2(40)  NOT NULL,
    nom_auteur              VARCHAR2(220) NOT NULL,
    code_type_media         VARCHAR2(20)  NOT NULL,
    titre                   VARCHAR2(150) NOT NULL,
    description             VARCHAR2(500),
    nom_fichier_original    VARCHAR2(255) NOT NULL,
    nom_fichier_stocke      VARCHAR2(255) NOT NULL,
    chemin_public           VARCHAR2(500) NOT NULL,
    type_mime               VARCHAR2(120) NOT NULL,
    taille_octets           NUMBER(12) NOT NULL,
    code_statut             VARCHAR2(40) DEFAULT 'en_attente_validation' NOT NULL,
    cree_le                 DATE DEFAULT SYSDATE NOT NULL,
    mis_a_jour_le           DATE,
    CONSTRAINT pk_media_publication PRIMARY KEY (identifiant),
    CONSTRAINT fk_media_auteur FOREIGN KEY (identifiant_auteur) REFERENCES compte_membre (identifiant),
    CONSTRAINT fk_media_type FOREIGN KEY (code_type_media) REFERENCES ref_type_media (code_type),
    CONSTRAINT fk_media_statut FOREIGN KEY (code_statut) REFERENCES ref_statut_publication (code_statut),
    CONSTRAINT ck_media_taille CHECK (taille_octets > 0)
);

CREATE INDEX ix_media_statut_date ON media_publication (code_statut, cree_le);
CREATE INDEX ix_media_auteur_date ON media_publication (identifiant_auteur, cree_le);

CREATE TABLE commande_locale (
    identifiant                 VARCHAR2(40)  NOT NULL,
    identifiant_utilisateur     VARCHAR2(40)  NOT NULL,
    nom_utilisateur             VARCHAR2(220) NOT NULL,
    produit                     VARCHAR2(160) NOT NULL,
    categorie                   VARCHAR2(80)  NOT NULL,
    code_statut                 VARCHAR2(30) DEFAULT 'en_attente' NOT NULL,
    cree_le                     DATE DEFAULT SYSDATE NOT NULL,
    mis_a_jour_le               DATE,
    CONSTRAINT pk_commande_locale PRIMARY KEY (identifiant),
    CONSTRAINT fk_commande_utilisateur FOREIGN KEY (identifiant_utilisateur) REFERENCES compte_membre (identifiant),
    CONSTRAINT fk_commande_statut FOREIGN KEY (code_statut) REFERENCES ref_statut_commande (code_statut)
);

CREATE INDEX ix_commande_utilisateur_date ON commande_locale (identifiant_utilisateur, cree_le);

CREATE TABLE dammier_puzzle (
    dammier_id              VARCHAR2(60) NOT NULL,
    titre                   VARCHAR2(160) NOT NULL,
    description             VARCHAR2(500),
    instruction             VARCHAR2(500),
    fen                     VARCHAR2(120) NOT NULL,
    trait                   VARCHAR2(1) DEFAULT 'w' NOT NULL,
    solution                VARCHAR2(1000),
    reponses                VARCHAR2(1000),
    indices                 VARCHAR2(1000),
    source_puzzle           VARCHAR2(80) DEFAULT 'pool_local' NOT NULL,
    actif                   NUMBER(1) DEFAULT 1 NOT NULL,
    cree_le                 DATE DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_dammier_puzzle PRIMARY KEY (dammier_id),
    CONSTRAINT ck_dammier_trait CHECK (trait IN ('w', 'b')),
    CONSTRAINT ck_dammier_actif CHECK (actif IN (0, 1))
);

CREATE TABLE dammier_score (
    dammier_score_id        VARCHAR2(60) NOT NULL,
    dammier_week_key        VARCHAR2(12) NOT NULL,
    dammier_puzzle_id       VARCHAR2(60) NOT NULL,
    dammier_user_id         VARCHAR2(40) NOT NULL,
    dammier_display_name    VARCHAR2(220) NOT NULL,
    dammier_moves_count     NUMBER(3) NOT NULL,
    dammier_elapsed_seconds NUMBER(6) NOT NULL,
    dammier_solved_at       DATE DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_dammier_score PRIMARY KEY (dammier_score_id),
    CONSTRAINT uq_dammier_score_user UNIQUE (dammier_week_key, dammier_puzzle_id, dammier_user_id),
    CONSTRAINT fk_dammier_score_puzzle FOREIGN KEY (dammier_puzzle_id) REFERENCES dammier_puzzle (dammier_id),
    CONSTRAINT fk_dammier_score_user FOREIGN KEY (dammier_user_id) REFERENCES compte_membre (identifiant),
    CONSTRAINT ck_dammier_moves CHECK (dammier_moves_count BETWEEN 1 AND 99),
    CONSTRAINT ck_dammier_seconds CHECK (dammier_elapsed_seconds BETWEEN 1 AND 7200)
);

CREATE INDEX ix_dammier_score_rank ON dammier_score (dammier_week_key, dammier_puzzle_id, dammier_moves_count, dammier_elapsed_seconds);

CREATE TABLE horaire_club (
    schedule_id         VARCHAR2(40) NOT NULL,
    season_label        VARCHAR2(120) NOT NULL,
    holiday_notice      VARCHAR2(320),
    updated_at          DATE DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_horaire_club PRIMARY KEY (schedule_id)
);

CREATE TABLE horaire_creneau (
    identifiant_creneau VARCHAR2(60) NOT NULL,
    schedule_id         VARCHAR2(40) NOT NULL,
    ordre_affichage     NUMBER(3) NOT NULL,
    jour                VARCHAR2(60) NOT NULL,
    horaire             VARCHAR2(80) NOT NULL,
    titre               VARCHAR2(180) NOT NULL,
    details             VARCHAR2(1400),
    jour_ferie          NUMBER(1) DEFAULT 0 NOT NULL,
    CONSTRAINT pk_horaire_creneau PRIMARY KEY (identifiant_creneau),
    CONSTRAINT uq_horaire_creneau_ordre UNIQUE (schedule_id, ordre_affichage),
    CONSTRAINT fk_horaire_creneau_club FOREIGN KEY (schedule_id) REFERENCES horaire_club (schedule_id) ON DELETE CASCADE,
    CONSTRAINT ck_horaire_creneau_ferie CHECK (jour_ferie IN (0, 1))
);

CREATE TABLE cache_api_externe (
    cle_cache           VARCHAR2(180) NOT NULL,
    service_nom         VARCHAR2(60) NOT NULL,
    contenu             CLOB NOT NULL,
    expire_le           DATE NOT NULL,
    mis_a_jour_le       DATE DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_cache_api_externe PRIMARY KEY (cle_cache),
    CONSTRAINT ck_cache_api_service CHECK (service_nom IN ('chesscom', 'google_avis'))
);

CREATE TABLE newsletter_abonnement (
    identifiant_abonnement VARCHAR2(50) NOT NULL,
    courriel               VARCHAR2(254) NOT NULL,
    courriel_normalise     VARCHAR2(254) NOT NULL,
    statut                 VARCHAR2(20) DEFAULT 'actif' NOT NULL,
    jeton_desabonnement    VARCHAR2(80) NOT NULL,
    consentement_version   VARCHAR2(40) NOT NULL,
    adresse_ip_hachee      VARCHAR2(128),
    agent_utilisateur      VARCHAR2(255),
    source_inscription     VARCHAR2(80) DEFAULT 'footer' NOT NULL,
    cree_le                DATE DEFAULT SYSDATE NOT NULL,
    confirme_le            DATE DEFAULT SYSDATE NOT NULL,
    desabonne_le           DATE,
    CONSTRAINT pk_newsletter_abonnement PRIMARY KEY (identifiant_abonnement),
    CONSTRAINT uq_newsletter_email UNIQUE (courriel_normalise),
    CONSTRAINT uq_newsletter_token UNIQUE (jeton_desabonnement),
    CONSTRAINT ck_newsletter_statut CHECK (statut IN ('actif', 'desabonne')),
    CONSTRAINT ck_newsletter_email CHECK (INSTR(courriel_normalise, '@') > 1)
);

CREATE INDEX ix_newsletter_statut ON newsletter_abonnement (statut, cree_le);

CREATE TABLE newsletter_envoi (
    identifiant_envoi       VARCHAR2(60) NOT NULL,
    identifiant_abonnement  VARCHAR2(50) NOT NULL,
    type_evenement          VARCHAR2(30) NOT NULL,
    titre_evenement         VARCHAR2(220) NOT NULL,
    url_evenement           VARCHAR2(500),
    sujet                   VARCHAR2(220) NOT NULL,
    statut_envoi            VARCHAR2(20) NOT NULL,
    erreur_envoi            VARCHAR2(1000),
    envoye_le               DATE DEFAULT SYSDATE NOT NULL,
    CONSTRAINT pk_newsletter_envoi PRIMARY KEY (identifiant_envoi),
    CONSTRAINT fk_newsletter_envoi_abonnement FOREIGN KEY (identifiant_abonnement) REFERENCES newsletter_abonnement (identifiant_abonnement),
    CONSTRAINT ck_newsletter_envoi_type CHECK (type_evenement IN ('article', 'cours', 'boutique', 'confirmation')),
    CONSTRAINT ck_newsletter_envoi_statut CHECK (statut_envoi IN ('envoye', 'echec', 'ignore'))
);

CREATE INDEX ix_newsletter_envoi_event ON newsletter_envoi (type_evenement, envoye_le);

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

MERGE INTO horaire_club cible
USING (
    SELECT
        'club_schedule' schedule_id,
        'Horaires 2025/2026 - Club d''Echecs' season_label,
        'Les horaires peuvent etre adaptes les jours feries. Consultez l''emploi du temps complet avant de vous deplacer.' holiday_notice
    FROM dual
) source
ON (cible.schedule_id = source.schedule_id)
WHEN MATCHED THEN UPDATE SET
    cible.season_label = source.season_label,
    cible.holiday_notice = source.holiday_notice
WHEN NOT MATCHED THEN INSERT (
    schedule_id, season_label, holiday_notice
) VALUES (
    source.schedule_id, source.season_label, source.holiday_notice
);

INSERT INTO schema_migration (
    version_schema,
    nom_migration,
    categorie,
    checksum,
    commentaire
) VALUES (
    '19c.0.0',
    'schema_initial_oracle_19c',
    'foundation',
    NULL,
    'Schema relationnel initial cible Oracle 19c avec donnees de reference.'
);

COMMIT;
