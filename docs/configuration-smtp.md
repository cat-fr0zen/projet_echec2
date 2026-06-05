# Configuration SMTP

Le site n'a besoin que d'un **fournisseur expéditeur** correctement configuré.
Une fois ce fournisseur branché, il pourra envoyer vers des destinataires Gmail,
Outlook, OVH, Infomaniak, etc.

## Principe

Le choix par défaut recommandé dans ce projet est maintenant **Gmail**.

Tu peux maintenant choisir un preset simple dans `.env` :

```dotenv
MAIL_PROVIDER=gmail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=adresse_gmail_du_club@gmail.com
MAIL_PASSWORD=mot_de_passe_application_gmail
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="adresse_gmail_du_club@gmail.com"
MAIL_FROM_NAME="Cavaliers d'Herouville"
```

Presets supportés :

- `gmail`
- `ovh`
- `infomaniak`
- `brevo`
- `outlook365`
- `custom`

Avec un preset, `MAIL_HOST`, `MAIL_PORT` et `MAIL_ENCRYPTION` sont préremplis.
Tu peux quand même les surcharger manuellement si ton offre impose un autre serveur.

## Exemples rapides

### Gmail

```dotenv
MAIL_PROVIDER=gmail
MAIL_MAILER=smtp
MAIL_USERNAME=ton.adresse@gmail.com
MAIL_PASSWORD=mot_de_passe_application
MAIL_FROM_ADDRESS=ton.adresse@gmail.com
MAIL_FROM_NAME="Cavaliers d'Herouville"
```

### Brevo

```dotenv
MAIL_PROVIDER=brevo
MAIL_MAILER=smtp
MAIL_USERNAME=ton_login_brevo
MAIL_PASSWORD=ta_cle_smtp_brevo
MAIL_FROM_ADDRESS=noreply@ton-domaine.fr
MAIL_FROM_NAME="Cavaliers d'Herouville"
```

### Infomaniak

```dotenv
MAIL_PROVIDER=infomaniak
MAIL_MAILER=smtp
MAIL_USERNAME=adresse@ton-domaine.fr
MAIL_PASSWORD=mot_de_passe_de_la_boite
MAIL_FROM_ADDRESS=adresse@ton-domaine.fr
MAIL_FROM_NAME="Cavaliers d'Herouville"
```

### Outlook / Microsoft 365

```dotenv
MAIL_PROVIDER=outlook365
MAIL_MAILER=smtp
MAIL_USERNAME=adresse@ton-domaine.fr
MAIL_PASSWORD=mot_de_passe_ou_secret_valide
MAIL_FROM_ADDRESS=adresse@ton-domaine.fr
MAIL_FROM_NAME="Cavaliers d'Herouville"
```

### OVH

```dotenv
MAIL_PROVIDER=ovh
MAIL_MAILER=smtp
MAIL_USERNAME=adresse@ton-domaine.fr
MAIL_PASSWORD=mot_de_passe_de_la_boite
MAIL_FROM_ADDRESS=adresse@ton-domaine.fr
MAIL_FROM_NAME="Cavaliers d'Herouville"
```

Note OVH :
- le preset `ovh` part sur `smtp.mail.ovh.net`
- certaines offres OVH utilisent un hôte plus spécifique, par exemple `pro?.mail.ovh.net`
- si besoin, garde `MAIL_PROVIDER=ovh` et surcharge `MAIL_HOST`

## Commandes utiles

Vérifier la config résolue :

```powershell
php artisan mail:config-check
```

Envoyer un email de test :

```powershell
php artisan mail:test-envoi destinataire@example.test
```

## Ce qu'il manque encore pour un vrai envoi

Je peux préparer le code, mais je ne peux pas inventer les identifiants réels.
Il faut donc connaitre au minimum :

- l'adresse email expéditrice
- le login SMTP
- le mot de passe SMTP ou mot de passe d'application

## Sources officielles

- Google : app passwords et envoi SMTP  
  https://support.google.com/mail/answer/185833  
  https://support.google.com/mail/answer/22370
- Brevo : SMTP relay  
  https://help.brevo.com/hc/en-us/articles/7924908994450-Send-transactional-emails-using-Brevo-SMTP
- Infomaniak : ports et serveur `mail.infomaniak.com`  
  https://www.infomaniak.com/fr/support/faq/468/comprendre-les-ports-et-protocoles-de-messagerie
- Microsoft 365 / Outlook : SMTP AUTH sur port 587  
  https://learn.microsoft.com/en-us/Exchange/clients-and-mobile-in-exchange-online/authenticated-client-smtp-submission
- OVHcloud : réglages SMTP selon l'offre  
  https://help.ovhcloud.com/csm/en-ca-mx-plan-outlook-windows-configuration?id=kb_article_view&sysparm_article=KB0052102  
  https://help.ovhcloud.com/csm/en-gb-email-pro-outlook-windows-configuration?id=kb_article_view&sysparm_article=KB0052275
