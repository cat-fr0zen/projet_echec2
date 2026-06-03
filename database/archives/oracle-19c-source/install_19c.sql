/*
    Installation Oracle 19c - Cavaliers d'Herouville

    A lancer depuis SQL*Plus, SQLcl ou Oracle SQL Developer dans le dossier 19c:
    SQL> @install_19c.sql
*/

PROMPT === Pre-check Oracle 19c ===
@@precheck_19c.sql

PROMPT === Creation du schema relationnel Oracle 19c ===
@@schema.sql

PROMPT === Verification securite Oracle 19c ===
@@security_verify.sql
