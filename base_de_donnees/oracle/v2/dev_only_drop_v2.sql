PROMPT ==========================================================
PROMPT ATTENTION: script destructif reserve a un schema de dev/test
PROMPT ==========================================================

SET DEFINE OFF;

BEGIN
    FOR objet IN (
        SELECT object_name, object_type
          FROM user_objects
         WHERE object_name IN (
             'PKG_SITE_ADMIN',
             'PKG_SITE_PORTAIL',
             'PKG_ARTICLE_EDITOR',
             'PKG_MAINTENANCE_SITE',
             'PKG_SCHEMA_MIGRATION',
             'VW_ADMIN_COMPTES',
             'VW_ADMIN_ARTICLES',
             'VW_ADMIN_MEDIAS',
             'VW_ARTICLE_BLOCS_ORDONNES',
             'VW_ADHESIONS_ACTIVES',
             'VW_CALENDRIER_ACTIVITES_PUBLIC',
             'VW_CATALOGUE_BOUTIQUE_PUBLIC',
             'VW_ROLES_COMPTES_ACTIFS',
             'VW_TOTAUX_COMMANDE_CLIENT'
         )
    ) LOOP
        BEGIN
            EXECUTE IMMEDIATE 'DROP ' || objet.object_type || ' ' || objet.object_name;
        EXCEPTION
            WHEN OTHERS THEN
                NULL;
        END;
    END LOOP;
END;
/

BEGIN
    FOR table_name IN (
        SELECT table_name
          FROM user_tables
         WHERE table_name IN (
             'MESSAGE_CONTACT',
             'PARTICIPATION_RENCONTRE_INTERCLUB',
             'RENCONTRE_INTERCLUB',
             'INSCRIPTION_TOURNOI_CLUB',
             'TOURNOI_CLUB',
             'INSCRIPTION_SESSION_ACTIVITE',
             'SESSION_ACTIVITE',
             'ACTIVITE_CLUB',
             'PAIEMENT_ADHESION',
             'ADHESION_MEMBRE',
             'SAISON_CLUB',
             'LIGNE_COMMANDE_CLIENT',
             'COMMANDE_CLIENT',
             'PRIX_PRODUIT',
             'MEDIA_PRODUIT',
             'PRODUIT',
             'ARTICLE_BLOC',
             'MEDIA_ARTICLE',
             'ELEMENT_ALBUM_MEDIA',
             'ALBUM_MEDIA',
             'REVISION_MEDIA',
             'AUTORISATION_DROITS_MEDIA',
             'REFERENCE_EXTERNE_MEDIA',
             'CHARGE_BINAIRE_MEDIA',
             'RESSOURCE_MEDIA',
             'REVISION_ARTICLE',
             'ARTICLE',
             'JOURNAL_CONNEXION',
             'CONSENTEMENT_COOKIE_VISITEUR',
             'CONSENTEMENT_MEMBRE',
             'CLASSEMENT_MEMBRE',
             'COMPTE_ROLE',
             'PROFIL_MEMBRE',
             'COMPTE_MEMBRE',
             'DOCUMENT_JURIDIQUE_VERSION',
             'TYPE_DOCUMENT_JURIDIQUE',
             'PARAMETRE_APPLICATION',
             'JOURNAL_AUDIT_ADMINISTRATION',
             'REF_TYPE_CLASSEMENT',
             'REF_STATUT_INSCRIPTION_TOURNOI',
             'REF_STATUT_TOURNOI',
             'REF_STATUT_INSCRIPTION_SESSION',
             'REF_NIVEAU_JOUEUR',
             'REF_TYPE_ACTIVITE',
             'REF_STATUT_ADHESION',
             'REF_TYPE_ADHESION',
             'REF_STATUT_COMMANDE',
             'REF_STATUT_PRODUIT',
             'REF_CATEGORIE_PRODUIT',
             'REF_STATUT_DROITS_MEDIA',
             'REF_STATUT_MEDIA',
             'REF_MODE_STOCKAGE_MEDIA',
             'REF_TYPE_MEDIA',
             'REF_TYPE_BLOC_ARTICLE',
             'REF_DECISION_REVISION_ARTICLE',
             'REF_STATUT_ARTICLE',
             'REF_TYPE_CONSENTEMENT',
             'REF_STATUT_COMPTE',
             'REF_ROLE_COMPTE',
             'SCHEMA_MIGRATION'
         )
    ) LOOP
        BEGIN
            EXECUTE IMMEDIATE 'DROP TABLE ' || table_name.table_name || ' CASCADE CONSTRAINTS PURGE';
        EXCEPTION
            WHEN OTHERS THEN
                NULL;
        END;
    END LOOP;
END;
/

PROMPT Roles Oracle non supprimes automatiquement.
PROMPT Si besoin, demander a un DBA de retirer manuellement:
PROMPT - RL_SITE_APP
PROMPT - RL_SITE_ADMIN_OPS
PROMPT - RL_SITE_AUDIT
