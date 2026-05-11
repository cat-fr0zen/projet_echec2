SET DEFINE OFF;
SET SERVEROUTPUT ON;
WHENEVER SQLERROR EXIT FAILURE;

PROMPT ============================================
PROMPT Installation Oracle v2 - club d'echecs
PROMPT ============================================

@@migrations/2.0.0_foundation.sql
@@migrations/2.0.1_reference_tables.sql
@@migrations/2.0.2_accounts_and_governance.sql
@@migrations/2.0.3_content_media.sql
@@migrations/2.0.4_commerce_and_club.sql
@@migrations/2.0.5_views_and_packages.sql
@@migrations/2.0.6_security_and_seed.sql
@@verify_v2.sql

PROMPT ============================================
PROMPT Installation Oracle v2 terminee
PROMPT ============================================
