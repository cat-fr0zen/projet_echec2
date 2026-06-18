# Audit accessibilite et responsive

Ce projet dispose maintenant d'une base minimale pour verifier les points les plus sensibles avant publication.

## Controles rapides a refaire avant mise en ligne

- Verifier le clavier seul : ouverture/fermeture des modales, menu burger, navigation dans les formulaires.
- Verifier les contrastes en theme clair et sombre sur :
  - navigation
  - boutons d'action
  - damier et pieces
  - messages de confirmation / suppression
- Verifier l'affichage mobile sur les largeurs :
  - `360px`
  - `390px`
  - `768px`
  - `1024px`
- Verifier les zones critiques :
  - page d'accueil
  - authentification
  - page Cours
  - administration
  - boutique

## Verifications techniques deja posees

- En-tetes HTTP de securite testes :
  - `Content-Security-Policy`
  - `Strict-Transport-Security`
  - `X-Frame-Options`
  - `X-Content-Type-Options`
  - `Referrer-Policy`
  - `Permissions-Policy`
- Cookies renforces :
  - session chiffree
  - `HttpOnly`
  - `SameSite=Strict`
  - HTTPS forcable par configuration
- Proxy inverse pris en charge pour la detection HTTPS.

## Recommandations d'outillage pour la suite

- Ajouter un passage Lighthouse sur accueil, cours, admin et boutique.
- Ajouter un passage Axe sur les modales, formulaires et menus.
- Capturer au moins un jeu d'ecrans desktop + mobile a chaque livraison importante.

## Points encore a surveiller

- Les pages legacy qui chargent des donnees depuis une base partielle doivent toujours retomber proprement sur un mode degrade.
- Les contenus ajoutes par l'administration doivent rester verifies manuellement :
  - titres
  - textes alternatifs d'images
  - ordre logique des sections
  - PDF accessibles si possible
