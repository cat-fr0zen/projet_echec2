<p>Bonjour {{ $nomDestinataire !== '' ? $nomDestinataire : 'membre du club' }},</p>

<p>
    La nouvelle saison d'adhesion {{ $saisonCible }} a commence.
    Ton adhesion precedente n'est plus active depuis le 1er septembre.
</p>

<p>
    Pour retrouver l'acces complet reserve aux adherents, connecte-toi a ton compte
    puis renouvelle ton adhesion depuis la boutique du site.
</p>

<p>
    <a href="{{ $urlBoutique }}">Ouvrir la boutique du club</a>
    <br>
    <a href="{{ $urlProfil }}">Voir mon profil membre</a>
</p>

<p>
    Si tu as deja relance ton adhesion, tu peux ignorer ce message.
    L'activation sera remise en place apres validation de la demande par le club.
</p>
