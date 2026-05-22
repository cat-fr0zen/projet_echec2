# Base Oracle 10g

Ce dossier est le socle relationnel pour remplacer les fichiers JSON metier du site.

Fichiers principaux:
- `schema.sql`: schema complet avec tables, cles primaires, cles etrangeres, contraintes et index.
- `migrations/`: migrations separees par categorie.
- `security_verify.sql`: controle lecture seule des contraintes, index, privileges et donnees sensibles.
- `change_journal_template.sql`: modele a copier pour les futurs ajouts, suppressions ou modifications.
- `install_10g.sql`: installation ordonnee sur un schema Oracle vide.

Configuration PHP/XAMPP attendue:
- Activer l'extension `oci8` dans le PHP utilise par Apache.
- Definir les variables d'environnement `ORACLE_HOST`, `ORACLE_PORT`, `ORACLE_SERVICE`, `ORACLE_USER`, `ORACLE_PASSWORD`.
- Optionnel: `ORACLE_CHARSET=AL32UTF8`.

Securite:
- Le compte Oracle de l'application doit avoir uniquement les droits necessaires sur son schema.
- Ne jamais utiliser `GRANT DBA` pour le site.
- Les requetes PHP doivent utiliser des variables liees OCI, jamais de concatenation SQL.
- Les fichiers `donnees/`, `base_de_donnees/` et `tests/` ne doivent pas etre servis en HTTP.

Note importante:
- Oracle 10g ne supporte pas les colonnes `IDENTITY`, `FETCH FIRST`, `OFFSET`, ni les fonctions JSON modernes.
- Le site peut recevoir ou emettre du JSON pour les APIs et certaines interactions navigateur, mais les donnees metier persistantes ne doivent plus etre stockees en fichiers JSON.
