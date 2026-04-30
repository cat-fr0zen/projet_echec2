# Modèle de données Oracle

## Objectif

Ce schéma cible est conçu pour la future version `Laravel + Oracle` du site de l'association d'échecs.
Il est organisé au plus près de la **forme normale de Boyce-Codd (BCNF)** afin que :

- un fait métier ne soit stocké qu'à un seul endroit
- les valeurs de référence soient isolées
- les workflows juridiques et éditoriaux restent traçables
- la gestion des médias reste maintenable après transmission

## Domaines principaux

### Comptes

- `compte_membre`
- `profil_membre`
- `role_compte`
- `statut_compte`

Le compte stocke l'authentification et le cycle de vie.
Le profil stocke les données personnelles propres au membre, et non à l'authentification.

### Consentement et conformité

- `consentement_cookie_visiteur`
- `consentement_membre`
- `type_consentement`

Cette séparation permet au site de conserver une preuve du consentement cookies pour les visiteurs anonymes et des consentements juridiques ou de publication pour les membres authentifiés.

### Workflow éditorial

- `article`
- `revision_article`
- `statut_publication`
- `type_decision_revision`

L'état de publication n'est pas codé en dur sous forme de texte libre dans la table des articles.
Il est normalisé via des tables de référence afin que la modération puisse évoluer sans dérive de schéma.

### Médias et droits

- `ressource_media`
- `charge_binaire_media`
- `reference_externe_media`
- `autorisation_droits_media`
- `type_media`
- `type_usage_media`
- `statut_droits_media`
- `mode_stockage_media`
- `album_media`
- `element_album_media`
- `media_article`
- `media_produit`

Le modèle sépare volontairement :

- les métadonnées des médias
- les charges binaires stockées dans Oracle
- les références vers un stockage externe
- les droits et autorisations de publication

C'est plus propre que de mélanger stockage de fichier, droits et contexte d'usage dans une seule table.

### Boutique

- `produit`
- `categorie_produit`
- `statut_produit`
- `prix_produit`
- `commande_client`
- `ligne_commande_client`
- `statut_commande`

Les prix sont historisés séparément du produit afin de ne pas réécrire l'historique commercial à chaque changement.
Les totaux de commande sont calculés volontairement à partir de `ligne_commande_client` via une vue, plutôt que dupliqués dans les tables de commande.

## Pourquoi ce modèle est proche de la BCNF

### 1. L'authentification est séparée de l'identité

`compte_membre` stocke les faits de connexion.
`profil_membre` stocke les faits de profil.

Cela évite de mettre toutes les préoccupations utilisateur dans une seule table.

### 2. Les énumérations juridiques sont normalisées

Les rôles, statuts de compte, statuts de publication, décisions de relecture, types de médias, modes de stockage et statuts de produit vivent tous dans des tables séparées.

Cela évite des dépendances transitives comme :

- `code_statut -> libelle_statut`
- `code_role -> libelle_role`

au sein des tables opérationnelles.

### 3. Le stockage média n'est pas surchargé

Une image ou une vidéo peut être stockée :

- dans Oracle sous forme de BLOB
- hors Oracle via une URI référencée

Au lieu d'empiler des colonnes nullables pour chaque stratégie de stockage dans une seule ligne, le stockage est réparti dans des tables dédiées.

### 4. Les droits sont distincts du fichier lui-même

L'existence d'un fichier média n'implique pas le droit de le publier.
`autorisation_droits_media` stocke cette couche juridique séparément.

## Chemin de migration recommandé depuis le prototype

1. migrer `utilisateurs.json` vers :
   - `compte_membre`
   - `profil_membre`
2. migrer `articles.json` vers :
   - `article`
3. laisser les envois de médias désactivés tant que :
   - le workflow de droits
   - la stratégie de stockage
   - la modération administrateur
   ne sont pas validés
4. puis seulement ajouter :
   - `ressource_media`
   - `autorisation_droits_media`
   - `album_media`
   - les tables de boutique

## Recommandation de stockage pour les images et vidéos

L'approche la plus propre en production est :

- Oracle stocke les métadonnées, les droits et les relations
- un stockage objet ou un stockage de fichiers géré conserve les binaires lourds

Si vous devez stocker directement les fichiers dans Oracle, `charge_binaire_media` est prêt pour cela.



