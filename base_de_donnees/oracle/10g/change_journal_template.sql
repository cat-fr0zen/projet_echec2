/*
    Modele de changement futur - Oracle 10g
    Copier ce fichier avant toute evolution de base:
    YYYY.MM.DD_categorie_description.sql

    CHECK -> PLAN -> APPLY -> VERIFY -> ROLLBACK
*/

/*
CHECK
-----
Objectif:
- verifier l'etat actuel avant changement;
- ne rien modifier ici.
*/

SELECT table_name
  FROM user_tables
 WHERE table_name = UPPER('<TABLE_CIBLE>');

/*
PLAN
----
Description:
- demandeur: <NOM>
- raison: <RAISON_METIER>
- categorie: <accounts|content|media|commerce|game|schedule|security>
- risque principal: <RISQUE>
- fenetre d'application: <DATE_HEURE>
*/

/*
APPLY
-----
Regles:
- preferer ALTER/INSERT/UPDATE ciblés;
- pour UPDATE/DELETE, faire un SELECT preview avec le meme filtre;
- utiliser des variables liees dans l'application, jamais de concatenation SQL.
*/

-- Exemple non destructif:
-- ALTER TABLE <TABLE_CIBLE> ADD (<NOUVELLE_COLONNE> VARCHAR2(120));

INSERT INTO audit_changement_base (
    identifiant_changement,
    categorie,
    operation,
    objet_cible,
    description,
    demandeur,
    applique_par,
    verification,
    rollback_prevu
) VALUES (
    '<YYYYMMDD_CATEGORIE_DESCRIPTION>',
    '<CATEGORIE>',
    'MIGRATION',
    '<OBJET_CIBLE>',
    '<DESCRIPTION>',
    '<DEMANDEUR>',
    USER,
    '<REQUETE_DE_VERIFICATION>',
    '<PLAN_DE_ROLLBACK>'
);

/*
VERIFY
------
Verifier que le changement est applique et que les contraintes restent valides.
*/

SELECT object_type, object_name, status
  FROM user_objects
 WHERE status <> 'VALID';

/*
ROLLBACK
--------
En production, le rollback est une nouvelle migration forward-only.
Documenter ici la mitigation, ne pas lancer de DROP/DELETE sans sauvegarde.
*/

-- COMMIT volontaire une fois les verifications terminees.
-- COMMIT;
