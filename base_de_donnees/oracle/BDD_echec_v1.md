# BDD Echec v1 (Oracle)

## Objectif

Ce document decrit la base cible du site de club d'echecs, alignee avec le script unique [BDD_echec_v1.sql](/C:/DEV/vscode_workspace/Projet_echec2/base_de_donnees/oracle/BDD_echec_v1.sql).

La base couvre:
- comptes membres et authentification
- profil, consentements et conformite
- workflow editorial (articles + moderation)
- mediatheque et droits de diffusion
- boutique, produits et commandes
- fonctionnement club (saisons, adhesions, activites, tournois, interclubs)
- maintenance automatique (triggers, vues, package PL/SQL, scheduler)

## Domaines fonctionnels

### 1) Comptes et profils

Tables principales:
- `compte_membre`
- `profil_membre`
- `role_compte`
- `statut_compte`
- `journal_connexion`

Points importants:
- email unique en lowercase via index fonctionnel
- mot de passe hache stocke au niveau compte
- informations personnelles au niveau profil
- pseudo Chess.com et elo (FIDE/rapid/blitz/bullet) en option

### 2) Conformite et consentements

Tables principales:
- `type_consentement`
- `consentement_cookie_visiteur`
- `consentement_membre`

Points importants:
- preuve des consentements visiteurs (cookies/theme)
- preuve des consentements membres (version juridique)
- conservation exploitable pour audit

### 3) Contenu editorial

Tables principales:
- `article`
- `revision_article`
- `statut_publication`
- `type_decision_revision`

Points importants:
- cycle de moderation normalise (`en_attente_validation`, `publie`, `archive`, etc.)
- date de publication auto remplie par trigger quand statut = `publie`

### 4) Mediatheque et droits

Tables principales:
- `ressource_media`
- `charge_binaire_media`
- `reference_externe_media`
- `autorisation_droits_media`
- `album_media`
- `element_album_media`
- `media_article`
- `media_produit`

Points importants:
- separation metadata / binaire / stockage externe
- verification des droits de diffusion
- vue `vw_ressources_media_pretes_publication` pour filtrer les medias publiables

### 5) Boutique

Tables principales:
- `produit`
- `prix_produit`
- `categorie_produit`
- `statut_produit`
- `commande_client`
- `ligne_commande_client`
- `statut_commande`
- `media_produit`

Points importants:
- prix historises dans `prix_produit`
- total commande derive via vue `vw_totaux_commande_client`

### 6) Fonctionnement club (sport)

Tables principales:
- `saison_club`
- `adhesion_membre`
- `paiement_adhesion`
- `type_adhesion`
- `statut_adhesion`
- `activite_club`
- `session_activite`
- `inscription_session_activite`
- `type_activite`
- `niveau_joueur`
- `tournoi_club`
- `inscription_tournoi_club`
- `rencontre_interclub`
- `participation_rencontre_interclub`

Points importants:
- une adhesion par membre et par saison
- suivi montant attendu vs montant paye
- trigger de recalcul du `montant_paye` depuis `paiement_adhesion`
- calendrier des sessions exploitable via `vw_calendrier_activites_public`
- gestion des inscriptions tournois et resultats interclubs

### 7) Contact site

Table principale:
- `message_contact`

Point important:
- suivi des messages traites/non traites

## Maintenance et exploitation

Le script inclut:
- triggers de normalisation et horodatage
- vues metier de lecture
- package `pkg_maintenance_site`
- job scheduler `JOB_MAINTENANCE_QUOTIDIENNE_SITE` (02:00 chaque jour)

Le package de maintenance execute:
- cloture droits media expires
- archivage articles en attente trop anciens
- expiration catalogue boutique
- purge consentements visiteurs anciens
- cloture adhesions expirees
- marquage des tournois termines

## Lancement

Depuis SQL*Plus ou SQLcl:

```sql
@BDD_echec_v1.sql
```

Le script est concu pour un deploiement initial complet (schema + donnees de reference + maintenance).

