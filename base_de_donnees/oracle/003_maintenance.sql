-- PL/SQL maintenance layer.
-- The goal is to keep the schema readable and operable after handover:
-- 1. normalize technical values on write
-- 2. update timestamps automatically
-- 3. archive or expire stale records through a scheduled package

CREATE OR REPLACE TRIGGER trg_compte_membre_biu
BEFORE INSERT OR UPDATE ON compte_membre
FOR EACH ROW
BEGIN
    :NEW.adresse_courriel := LOWER(TRIM(:NEW.adresse_courriel));

    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_profil_membre_biu
BEFORE INSERT OR UPDATE ON profil_membre
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_article_biu
BEFORE INSERT OR UPDATE ON article
FOR EACH ROW
DECLARE
    v_publie_status_id statut_publication.identifiant_statut_publication%TYPE;
BEGIN
    SELECT identifiant_statut_publication
      INTO v_publie_status_id
      FROM statut_publication
     WHERE code_statut = 'publie';

    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
        :NEW.soumis_le := COALESCE(:NEW.soumis_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;

    IF :NEW.identifiant_statut_publication = v_publie_status_id AND :NEW.publie_le IS NULL THEN
        :NEW.publie_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_ressource_media_biu
BEFORE INSERT OR UPDATE ON ressource_media
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_charge_binaire_media_biu
BEFORE INSERT OR UPDATE ON charge_binaire_media
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_reference_externe_media_biu
BEFORE INSERT OR UPDATE ON reference_externe_media
FOR EACH ROW
BEGIN
    :NEW.fournisseur_stockage := TRIM(:NEW.fournisseur_stockage);
    :NEW.uri_stockage := TRIM(:NEW.uri_stockage);
    :NEW.empreinte_sha256 := LOWER(TRIM(:NEW.empreinte_sha256));

    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_autorisation_droits_media_biu
BEFORE INSERT OR UPDATE ON autorisation_droits_media
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_album_media_biu
BEFORE INSERT OR UPDATE ON album_media
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_produit_biu
BEFORE INSERT OR UPDATE ON produit
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_commande_client_biu
BEFORE INSERT OR UPDATE ON commande_client
FOR EACH ROW
BEGIN
    :NEW.courriel_facturation := LOWER(TRIM(:NEW.courriel_facturation));

    IF INSERTING THEN
        :NEW.cree_le := COALESCE(:NEW.cree_le, SYSTIMESTAMP);
        :NEW.mis_a_jour_le := COALESCE(:NEW.mis_a_jour_le, SYSTIMESTAMP);
        :NEW.commandee_le := COALESCE(:NEW.commandee_le, SYSTIMESTAMP);
    ELSE
        :NEW.mis_a_jour_le := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE VIEW vw_ressources_media_pretes_publication AS
SELECT ma.identifiant_ressource_media,
       ma.identifiant_type_media,
       ma.identifiant_mode_stockage_media,
       ma.identifiant_compte_depot,
       ma.nom_fichier_original,
       ma.type_mime,
       ma.taille_octets,
       ma.largeur_px,
       ma.hauteur_px,
       ma.duree_secondes,
       ma.texte_alternatif,
       ma.date_capture,
       ma.cree_le,
       ma.mis_a_jour_le
  FROM ressource_media ma
 WHERE EXISTS (
        SELECT 1
          FROM autorisation_droits_media mrg
          JOIN statut_droits_media mrs
            ON mrs.identifiant_statut_droits_media = mrg.identifiant_statut_droits_media
         WHERE mrg.identifiant_ressource_media = ma.identifiant_ressource_media
           AND mrs.code_statut = 'accorde'
           AND (mrg.expire_le IS NULL OR mrg.expire_le >= SYSTIMESTAMP)
      );
/

CREATE OR REPLACE VIEW vw_totaux_commande_client AS
SELECT coi.identifiant_commande_client,
       SUM(coi.quantite * coi.prix_unitaire) AS total_montant
  FROM ligne_commande_client coi
 GROUP BY coi.identifiant_commande_client;
/

CREATE OR REPLACE PACKAGE pkg_maintenance_site AS
    PROCEDURE cloturer_droits_media_expires;
    PROCEDURE archiver_articles_en_attente_anciens(p_seuil_jours NUMBER DEFAULT 180);
    PROCEDURE expirer_catalogue_boutique;
    PROCEDURE purger_anciens_consentements_visiteurs(p_mois_conservation NUMBER DEFAULT 24);
    PROCEDURE lancer_maintenance_quotidienne;
END pkg_maintenance_site;
/

CREATE OR REPLACE PACKAGE BODY pkg_maintenance_site AS
    PROCEDURE cloturer_droits_media_expires IS
        v_expire_status_id statut_droits_media.identifiant_statut_droits_media%TYPE;
        v_accorde_status_id statut_droits_media.identifiant_statut_droits_media%TYPE;
    BEGIN
        SELECT identifiant_statut_droits_media
          INTO v_expire_status_id
          FROM statut_droits_media
         WHERE code_statut = 'expire';

        SELECT identifiant_statut_droits_media
          INTO v_accorde_status_id
          FROM statut_droits_media
         WHERE code_statut = 'accorde';

        UPDATE autorisation_droits_media
           SET identifiant_statut_droits_media = v_expire_status_id,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_droits_media = v_accorde_status_id
           AND expire_le IS NOT NULL
           AND expire_le < SYSTIMESTAMP;
    END cloturer_droits_media_expires;

    PROCEDURE archiver_articles_en_attente_anciens(p_seuil_jours NUMBER DEFAULT 180) IS
        v_en_attente_status_id statut_publication.identifiant_statut_publication%TYPE;
        v_archive_status_id statut_publication.identifiant_statut_publication%TYPE;
    BEGIN
        SELECT identifiant_statut_publication
          INTO v_en_attente_status_id
          FROM statut_publication
         WHERE code_statut = 'en_attente_validation';

        SELECT identifiant_statut_publication
          INTO v_archive_status_id
          FROM statut_publication
         WHERE code_statut = 'archive';

        UPDATE article
           SET identifiant_statut_publication = v_archive_status_id,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_publication = v_en_attente_status_id
           AND soumis_le < SYSTIMESTAMP - NUMTODSINTERVAL(p_seuil_jours, 'DAY');
    END archiver_articles_en_attente_anciens;

    PROCEDURE expirer_catalogue_boutique IS
        v_actif_status_id statut_produit.identifiant_statut_produit%TYPE;
        v_expire_status_id statut_produit.identifiant_statut_produit%TYPE;
    BEGIN
        SELECT identifiant_statut_produit
          INTO v_actif_status_id
          FROM statut_produit
         WHERE code_statut = 'actif';

        SELECT identifiant_statut_produit
          INTO v_expire_status_id
          FROM statut_produit
         WHERE code_statut = 'expire';

        UPDATE produit
           SET identifiant_statut_produit = v_expire_status_id,
               mis_a_jour_le = SYSTIMESTAMP
         WHERE identifiant_statut_produit = v_actif_status_id
           AND disponible_jusqua IS NOT NULL
           AND disponible_jusqua < SYSTIMESTAMP;
    END expirer_catalogue_boutique;

    PROCEDURE purger_anciens_consentements_visiteurs(p_mois_conservation NUMBER DEFAULT 24) IS
    BEGIN
        DELETE FROM consentement_cookie_visiteur
         WHERE accepte_le < ADD_MONTHS(SYSTIMESTAMP, -p_mois_conservation);
    END purger_anciens_consentements_visiteurs;

    PROCEDURE lancer_maintenance_quotidienne IS
    BEGIN
        cloturer_droits_media_expires;
        archiver_articles_en_attente_anciens;
        expirer_catalogue_boutique;
        purger_anciens_consentements_visiteurs;
        COMMIT;
    END lancer_maintenance_quotidienne;
END pkg_maintenance_site;
/

BEGIN
    BEGIN
        DBMS_SCHEDULER.DROP_JOB(
            job_name => 'JOB_MAINTENANCE_QUOTIDIENNE_SITE',
            force => TRUE
        );
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -27475 THEN
                RAISE;
            END IF;
    END;

    DBMS_SCHEDULER.CREATE_JOB(
        job_name => 'JOB_MAINTENANCE_QUOTIDIENNE_SITE',
        job_type => 'PLSQL_BLOCK',
        job_action => 'BEGIN pkg_maintenance_site.lancer_maintenance_quotidienne; END;',
        start_date => SYSTIMESTAMP,
        repeat_interval => 'FREQ=DAILY;BYHOUR=02;BYMINUTE=00;BYSECOND=00',
        enabled => TRUE,
        comments => 'Maintenance automatique du site de l''association d''échecs'
    );
END;
/



