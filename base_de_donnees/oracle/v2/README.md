# Oracle v2

Cette v2 remplace la logique "un seul gros script" par une installation modulaire,
versionnee et plus exploitable dans la duree.

## Objectifs

- remettre la base sur un modele coherent et normalise
- corriger les incoherences structurelles de l'ancien schema monolithique
- rendre l'administration quotidienne possible sans SQL sauvage
- separer schema, donnees de reference, vues, packages, securite et verification
- preparer une migration propre depuis le prototype PHP/JSON actuel

## Hypothese Oracle

Cette v2 part sur une base Oracle 19c ou 12cR2+.
Cette hypothese est volontaire:

- le projet v1 utilisait deja les colonnes `IDENTITY`
- le projet cible une exploitation avec `DBMS_SCHEDULER`

Si vous devez supporter Oracle 11g, il faudra remplacer les `IDENTITY` par des
sequences + triggers avant execution.

## Ce que la v2 apporte

### 1. Normalisation plus stricte

- separation claire entre `compte_membre`, `profil_membre` et `compte_role`
- classement du joueur normalise dans `classement_membre` au lieu de colonnes Elo repetees
- gestion des documents juridiques versionnes avec preuve de consentement
- statuts de contenu/media/club separes par domaine

### 2. Administration non technique

- vues d'administration lisibles:
  - `vw_admin_comptes`
  - `vw_admin_articles`
  - `vw_admin_medias`
  - `vw_article_blocs_ordonnes`
  - `vw_adhesions_actives`
  - `vw_calendrier_activites_public`
- table `journal_audit_administration` pour tracer les operations
- table `parametre_application` pour les reglages metier sans changement de code
- package `pkg_site_portail` pour le front-office
- package `pkg_site_admin` pour les operations sensibles reservees a l'administration
- package `pkg_article_editor` pour creer des articles structures par blocs

### 3. Securite

- roles Oracle dedies aux usages applicatifs et d'audit
- execution des operations via packages plutot que DML direct
- requetes ciblees par codes metier et contraintes explicites
- article editor stocke en blocs normalises au lieu d'un HTML libre dangereux
- historique de moderation et des consentements

### 4. Maintenance et migrations

- table `schema_migration`
- migrations separees et numeotees
- package `pkg_maintenance_site`
- script de verification apres installation
- script de destruction reserve au DEV

## Ordre d'installation

Depuis SQL*Plus ou SQLcl, dans le dossier `v2`:

```sql
@install_v2.sql
```

Le script appelle les migrations suivantes:

1. `2.0.0_foundation.sql`
2. `2.0.1_reference_tables.sql`
3. `2.0.2_accounts_and_governance.sql`
4. `2.0.3_content_media.sql`
5. `2.0.4_commerce_and_club.sql`
6. `2.0.5_views_and_packages.sql`
7. `2.0.6_security_and_seed.sql`
8. `2.0.7_article_editor_blocks.sql`
9. `verify_v2.sql`

Migration de donnees disponible en modele:

- `migrations/2.1.0_import_prototype_json_template.sql`

## Strategie de migration depuis le prototype JSON

Le site PHP tourne encore sur:

- `donnees/utilisateurs.json`
- `donnees/articles.json`
- `donnees/medias.json`
- `donnees/commandes.json`

La v2 n'ecrase pas ce runtime. Elle fournit une cible Oracle plus saine pour la
transition.

Mapping de reference:

- `utilisateurs.json.role=connecte` -> `ref_role_compte.code_role = 'membre'`
- `utilisateurs.json.role=adherent` -> `ref_role_compte.code_role = 'adherent'`
- `utilisateurs.json.role=admin` -> `ref_role_compte.code_role = 'admin'`
- `utilisateurs.json.statut_compte` -> `ref_statut_compte.code_statut`
- `utilisateurs.json.statut_adhesion` -> `ref_statut_adhesion.code_statut`
- `articles.json.blocs[]` -> `article_bloc` avec `ref_type_bloc_article`
- `articles.json.auteur_affiche` -> `article.auteur_affiche`

## Verification

Apres installation:

```sql
@verify_v2.sql
```

Ce script controle:

- la presence des migrations enregistrees
- le statut des packages et vues critiques
- les donnees de reference minimales

## Rollback

En production:

- ne pas utiliser de `DROP` direct
- revenir par migration compensatrice
- restaurer depuis export si une migration destructive a deja touche les donnees

En environnement de developpement uniquement:

```sql
@dev_only_drop_v2.sql
```

## Limites volontaires

- la v2 ne branche pas encore le runtime PHP sur Oracle
- les imports JSON de production doivent passer par une etape de validation metier
- la creation du job scheduler n'est pas forcee automatiquement dans l'installation
  pour eviter une execution silencieuse sans privileges adaptes
