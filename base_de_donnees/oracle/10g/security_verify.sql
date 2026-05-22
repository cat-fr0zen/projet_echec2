/*
    Verification securite Oracle 10g
    A lancer apres schema.sql et les migrations.

    Ce script ne modifie rien. Il aide a controler:
    - contraintes PK/FK/CHECK presentes;
    - index sensibles presents;
    - privileges sortants trop larges;
    - objets invalides;
    - donnees de comptes minimales.
*/

PROMPT === Contraintes principales ===
SELECT table_name, constraint_name, constraint_type, status
  FROM user_constraints
 WHERE table_name IN (
    'COMPTE_MEMBRE',
    'ARTICLE',
    'ARTICLE_BLOC',
    'MEDIA_PUBLICATION',
    'COMMANDE_LOCALE',
    'DAMMIER_PUZZLE',
    'DAMMIER_SCORE',
    'HORAIRE_CLUB',
    'HORAIRE_CRENEAU'
 )
 ORDER BY table_name, constraint_type, constraint_name;

PROMPT === Index sensibles ===
SELECT index_name, table_name, uniqueness, status
  FROM user_indexes
 WHERE index_name IN (
    'UQ_COMPTE_MEMBRE_EMAIL',
    'UQ_COMPTE_MEMBRE_LICENCE_FFE',
    'IX_ARTICLE_STATUT_DATE',
    'IX_MEDIA_STATUT_DATE',
    'IX_DAMMIER_SCORE_RANK'
 )
 ORDER BY table_name, index_name;

PROMPT === Privileges donnes par ce schema ===
SELECT grantee, table_name, privilege, grantable
  FROM user_tab_privs_made
 ORDER BY grantee, table_name, privilege;

PROMPT === Objets invalides ===
SELECT object_type, object_name, status
  FROM user_objects
 WHERE status <> 'VALID'
 ORDER BY object_type, object_name;

PROMPT === Comptes avec mot de passe absent ou email non normalise ===
SELECT identifiant, courriel
  FROM compte_membre
 WHERE mot_de_passe_hache IS NULL
    OR TRIM(mot_de_passe_hache) IS NULL
    OR courriel_normalise <> LOWER(TRIM(courriel));

PROMPT === Licences FFE dupliquees apres normalisation ===
SELECT UPPER(numero_licence_federale) numero_licence_federale, COUNT(*) nombre
  FROM compte_membre
 WHERE numero_licence_federale IS NOT NULL
 GROUP BY UPPER(numero_licence_federale)
HAVING COUNT(*) > 1;

PROMPT === Migrations appliquees ===
SELECT version_schema, nom_migration, categorie, applique_le
  FROM schema_migration
 ORDER BY version_schema;
