PROMPT [2.1.0] Import prototype JSON template
PROMPT Ce script est un modele d'import et ne fait rien tant que les placeholders ne sont pas remplaces.

/*
Risque principal:
- importer des donnees incoherentes du prototype JSON dans la base Oracle v2

Hypotheses:
- la v2 est deja installee
- les donnees JSON ont ete relues et nettoyees
- les mots de passe restent des hashes applicatifs, jamais du clair

Pre-checks conseilles:
SELECT COUNT(*) FROM compte_membre;
SELECT code_role FROM ref_role_compte ORDER BY ordre_affichage;
SELECT code_saison FROM saison_club ORDER BY date_debut;

Strategie:
1. creer le compte via pkg_site_portail
2. attribuer les roles supplementaires via pkg_site_admin
3. mettre a jour le statut si besoin
4. ouvrir une adhesion si l'ancien JSON indiquait un adherent actif

Rollback:
- restaurer depuis export ou supprimer explicitement le compte cree dans un environnement de test
- ne jamais utiliser ce modele en production sans sauvegarde
*/

DECLARE
    v_identifiant_compte_membre NUMBER;
BEGIN
    /*
    Exemple de mapping depuis `donnees/utilisateurs.json`

    JSON source:
    {
      "courriel": "<email>",
      "mot_de_passe_hache": "<hash>",
      "prenom": "<prenom>",
      "nom": "<nom>",
      "date_naissance": "YYYY-MM-DD",
      "description_profil": "<bio>",
      "pseudo_chess": "<pseudo>",
      "role": "connecte|adherent|admin",
      "statut_compte": "actif|suspendu",
      "statut_adhesion": "aucune|active"
    }
    */

    pkg_site_portail.creer_compte_membre(
        p_email => '<email>',
        p_mot_de_passe_hache => '<password_hash>',
        p_prenom => '<prenom>',
        p_nom => '<nom>',
        p_date_naissance => TO_DATE('<yyyy-mm-dd>', 'YYYY-MM-DD'),
        p_biographie => '<description_profil>',
        p_pseudo_chess_com => '<pseudo_chess>',
        p_accepte_affichage_public => 'N',
        o_identifiant_compte_membre => v_identifiant_compte_membre
    );

    IF '<role_json>' = 'adherent' THEN
        pkg_site_admin.attribuer_role_compte(
            p_identifiant_compte_membre => v_identifiant_compte_membre,
            p_code_role => 'adherent',
            p_commentaire_attribution => 'Import prototype JSON'
        );
    ELSIF '<role_json>' = 'admin' THEN
        pkg_site_admin.attribuer_role_compte(
            p_identifiant_compte_membre => v_identifiant_compte_membre,
            p_code_role => 'admin',
            p_commentaire_attribution => 'Import prototype JSON'
        );
    END IF;

    IF '<statut_compte_json>' = 'suspendu' THEN
        pkg_site_admin.mettre_a_jour_statut_compte(
            p_identifiant_compte_membre => v_identifiant_compte_membre,
            p_code_statut_compte => 'suspendu'
        );
    END IF;

    /*
    Si le JSON indique `statut_adhesion = active`, creer d'abord une saison puis:

    pkg_site_admin.ouvrir_adhesion_membre(
        p_identifiant_compte_membre => v_identifiant_compte_membre,
        p_code_saison => '<code_saison>',
        p_code_type_adhesion => '<adulte|jeune|etudiant|soutien>',
        p_code_statut_adhesion => 'active',
        o_identifiant_adhesion_membre => <variable>
    );
    */
END;
/

PROMPT Verification conseilee
PROMPT SELECT * FROM vw_admin_comptes WHERE identifiant_compte_membre = <id_cree>;
