-- PL/SQL maintenance layer.
-- The goal is to keep the schema readable and operable after handover:
-- 1. normalize technical values on write
-- 2. update timestamps automatically
-- 3. archive or expire stale records through a scheduled package

CREATE OR REPLACE TRIGGER trg_member_account_biu
BEFORE INSERT OR UPDATE ON member_account
FOR EACH ROW
BEGIN
    :NEW.email_address := LOWER(TRIM(:NEW.email_address));

    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_member_profile_biu
BEFORE INSERT OR UPDATE ON member_profile
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_article_biu
BEFORE INSERT OR UPDATE ON article
FOR EACH ROW
DECLARE
    v_published_status_id publication_status.publication_status_id%TYPE;
BEGIN
    SELECT publication_status_id
      INTO v_published_status_id
      FROM publication_status
     WHERE status_code = 'published';

    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
        :NEW.submitted_at := COALESCE(:NEW.submitted_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;

    IF :NEW.publication_status_id = v_published_status_id AND :NEW.published_at IS NULL THEN
        :NEW.published_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_media_asset_biu
BEFORE INSERT OR UPDATE ON media_asset
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_media_binary_payload_biu
BEFORE INSERT OR UPDATE ON media_binary_payload
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_media_external_reference_biu
BEFORE INSERT OR UPDATE ON media_external_reference
FOR EACH ROW
BEGIN
    :NEW.storage_provider := TRIM(:NEW.storage_provider);
    :NEW.storage_uri := TRIM(:NEW.storage_uri);
    :NEW.checksum_sha256 := LOWER(TRIM(:NEW.checksum_sha256));

    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_media_rights_grant_biu
BEFORE INSERT OR UPDATE ON media_rights_grant
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_media_album_biu
BEFORE INSERT OR UPDATE ON media_album
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_product_biu
BEFORE INSERT OR UPDATE ON product
FOR EACH ROW
BEGIN
    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_customer_order_biu
BEFORE INSERT OR UPDATE ON customer_order
FOR EACH ROW
BEGIN
    :NEW.billing_email := LOWER(TRIM(:NEW.billing_email));

    IF INSERTING THEN
        :NEW.created_at := COALESCE(:NEW.created_at, SYSTIMESTAMP);
        :NEW.updated_at := COALESCE(:NEW.updated_at, SYSTIMESTAMP);
        :NEW.placed_at := COALESCE(:NEW.placed_at, SYSTIMESTAMP);
    ELSE
        :NEW.updated_at := SYSTIMESTAMP;
    END IF;
END;
/

CREATE OR REPLACE VIEW vw_media_assets_ready_for_publication AS
SELECT ma.media_asset_id,
       ma.media_type_id,
       ma.media_storage_mode_id,
       ma.uploaded_by_account_id,
       ma.original_filename,
       ma.mime_type,
       ma.byte_size,
       ma.width_px,
       ma.height_px,
       ma.duration_seconds,
       ma.alt_text,
       ma.capture_date,
       ma.created_at,
       ma.updated_at
  FROM media_asset ma
 WHERE EXISTS (
        SELECT 1
          FROM media_rights_grant mrg
          JOIN media_rights_status mrs
            ON mrs.media_rights_status_id = mrg.media_rights_status_id
         WHERE mrg.media_asset_id = ma.media_asset_id
           AND mrs.status_code = 'granted'
           AND (mrg.expires_at IS NULL OR mrg.expires_at >= SYSTIMESTAMP)
      );
/

CREATE OR REPLACE VIEW vw_customer_order_totals AS
SELECT coi.customer_order_id,
       SUM(coi.quantity * coi.unit_price) AS total_amount
  FROM customer_order_item coi
 GROUP BY coi.customer_order_id;
/

CREATE OR REPLACE PACKAGE pkg_site_maintenance AS
    PROCEDURE close_expired_media_rights;
    PROCEDURE archive_stale_pending_articles(p_days_threshold NUMBER DEFAULT 180);
    PROCEDURE expire_merch_catalog;
    PROCEDURE purge_old_visitor_consents(p_keep_months NUMBER DEFAULT 24);
    PROCEDURE run_daily_maintenance;
END pkg_site_maintenance;
/

CREATE OR REPLACE PACKAGE BODY pkg_site_maintenance AS
    PROCEDURE close_expired_media_rights IS
        v_expired_status_id media_rights_status.media_rights_status_id%TYPE;
        v_granted_status_id media_rights_status.media_rights_status_id%TYPE;
    BEGIN
        SELECT media_rights_status_id
          INTO v_expired_status_id
          FROM media_rights_status
         WHERE status_code = 'expired';

        SELECT media_rights_status_id
          INTO v_granted_status_id
          FROM media_rights_status
         WHERE status_code = 'granted';

        UPDATE media_rights_grant
           SET media_rights_status_id = v_expired_status_id,
               updated_at = SYSTIMESTAMP
         WHERE media_rights_status_id = v_granted_status_id
           AND expires_at IS NOT NULL
           AND expires_at < SYSTIMESTAMP;
    END close_expired_media_rights;

    PROCEDURE archive_stale_pending_articles(p_days_threshold NUMBER DEFAULT 180) IS
        v_pending_status_id publication_status.publication_status_id%TYPE;
        v_archived_status_id publication_status.publication_status_id%TYPE;
    BEGIN
        SELECT publication_status_id
          INTO v_pending_status_id
          FROM publication_status
         WHERE status_code = 'pending_review';

        SELECT publication_status_id
          INTO v_archived_status_id
          FROM publication_status
         WHERE status_code = 'archived';

        UPDATE article
           SET publication_status_id = v_archived_status_id,
               updated_at = SYSTIMESTAMP
         WHERE publication_status_id = v_pending_status_id
           AND submitted_at < SYSTIMESTAMP - NUMTODSINTERVAL(p_days_threshold, 'DAY');
    END archive_stale_pending_articles;

    PROCEDURE expire_merch_catalog IS
        v_active_status_id product_status.product_status_id%TYPE;
        v_expired_status_id product_status.product_status_id%TYPE;
    BEGIN
        SELECT product_status_id
          INTO v_active_status_id
          FROM product_status
         WHERE status_code = 'active';

        SELECT product_status_id
          INTO v_expired_status_id
          FROM product_status
         WHERE status_code = 'expired';

        UPDATE product
           SET product_status_id = v_expired_status_id,
               updated_at = SYSTIMESTAMP
         WHERE product_status_id = v_active_status_id
           AND available_until IS NOT NULL
           AND available_until < SYSTIMESTAMP;
    END expire_merch_catalog;

    PROCEDURE purge_old_visitor_consents(p_keep_months NUMBER DEFAULT 24) IS
    BEGIN
        DELETE FROM visitor_cookie_consent
         WHERE accepted_at < ADD_MONTHS(SYSTIMESTAMP, -p_keep_months);
    END purge_old_visitor_consents;

    PROCEDURE run_daily_maintenance IS
    BEGIN
        close_expired_media_rights;
        archive_stale_pending_articles;
        expire_merch_catalog;
        purge_old_visitor_consents;
        COMMIT;
    END run_daily_maintenance;
END pkg_site_maintenance;
/

BEGIN
    BEGIN
        DBMS_SCHEDULER.DROP_JOB(
            job_name => 'JOB_SITE_DAILY_MAINTENANCE',
            force => TRUE
        );
    EXCEPTION
        WHEN OTHERS THEN
            IF SQLCODE != -27475 THEN
                RAISE;
            END IF;
    END;

    DBMS_SCHEDULER.CREATE_JOB(
        job_name => 'JOB_SITE_DAILY_MAINTENANCE',
        job_type => 'PLSQL_BLOCK',
        job_action => 'BEGIN pkg_site_maintenance.run_daily_maintenance; END;',
        start_date => SYSTIMESTAMP,
        repeat_interval => 'FREQ=DAILY;BYHOUR=02;BYMINUTE=00;BYSECOND=00',
        enabled => TRUE,
        comments => 'Maintenance automatique du site de l''association d''échecs'
    );
END;
/
