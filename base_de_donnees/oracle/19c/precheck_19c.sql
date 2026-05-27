/*
    Pre-check Oracle 19c
    Lecture seule: ne modifie aucune donnee.
*/

PROMPT === Version serveur detectee ===
SELECT product, version, status
  FROM product_component_version
 WHERE UPPER(product) LIKE 'ORACLE DATABASE%';

PROMPT === Schema courant ===
SELECT USER schema_courant,
       SYS_CONTEXT('USERENV', 'SERVICE_NAME') service_name,
       SYS_CONTEXT('USERENV', 'DB_NAME') db_name
  FROM dual;

PROMPT === Objets existants du schema courant ===
SELECT object_type, COUNT(*) nombre
  FROM user_objects
 GROUP BY object_type
 ORDER BY object_type;
