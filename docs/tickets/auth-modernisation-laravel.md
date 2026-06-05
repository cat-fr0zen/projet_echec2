# Ticket technique - moderniser l'authentification legacy

## Priorite

Haute

## Pourquoi ce ticket existe

Le depot a ete durci sur les points les plus urgents, mais l'authentification reste encore basee sur une logique custom dans `LegacyActionHandler` et `LegacyPageRenderer`.

## Objectif

Migrer progressivement vers les mecanismes Laravel standards :

- guard Laravel
- middleware d'autorisation
- reset password
- verification d'email
- notifications Laravel
- event listeners de connexion/deconnexion

## Contraintes

- conserver la table `compte_membre`
- ne pas casser les roles existants `connecte`, `adherent`, `prof`, `admin`
- prevoir une migration douce depuis `$_SESSION['identifiant_utilisateur']`

## Definition of done

1. `User` etend `Authenticatable`
2. les pages protegees n'utilisent plus de controle d'acces dans le renderer
3. le reset password fonctionne de bout en bout
4. la verification email est activee pour les nouveaux comptes
5. les tests couvrent login, logout, reset password et verification email
