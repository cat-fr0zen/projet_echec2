SET PAGESIZE 200;
SET LINESIZE 200;

PROMPT ============================================
PROMPT Verification Oracle v2
PROMPT ============================================

PROMPT -- Migrations
SELECT version_schema, description_migration, applique_le
  FROM schema_migration
 ORDER BY applique_le;

PROMPT -- Objets critiques
SELECT object_name, object_type, status
  FROM user_objects
 WHERE object_name IN (
     'PKG_SCHEMA_MIGRATION',
     'PKG_SITE_PORTAIL',
     'PKG_SITE_ADMIN',
     'PKG_ARTICLE_EDITOR',
     'PKG_MAINTENANCE_SITE',
     'VW_ADMIN_COMPTES',
     'VW_ADMIN_ARTICLES',
     'VW_ADMIN_MEDIAS',
     'VW_ARTICLE_BLOCS_ORDONNES',
     'VW_ADHESIONS_ACTIVES',
     'VW_CALENDRIER_ACTIVITES_PUBLIC'
 )
 ORDER BY object_type, object_name;

PROMPT -- Comptages de reference
SELECT 'ref_role_compte' AS table_name, COUNT(*) AS total_rows FROM ref_role_compte
UNION ALL
SELECT 'ref_statut_compte', COUNT(*) FROM ref_statut_compte
UNION ALL
SELECT 'ref_statut_article', COUNT(*) FROM ref_statut_article
UNION ALL
SELECT 'ref_type_bloc_article', COUNT(*) FROM ref_type_bloc_article
UNION ALL
SELECT 'ref_statut_media', COUNT(*) FROM ref_statut_media
UNION ALL
SELECT 'ref_type_adhesion', COUNT(*) FROM ref_type_adhesion;

PROMPT -- Parametres applicatifs
SELECT cle_parametre, valeur_parametre, type_parametre
  FROM parametre_application
 ORDER BY cle_parametre;
