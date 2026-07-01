@include('errors._http-status', [
    'code' => 400,
    'label' => 'Erreur 400',
    'title' => 'Vous etes en echec &amp; mat.',
    'message' => "La requete envoyee au serveur n'est pas comprise.",
    'hint' => "Verifie l'adresse ou recharge la page depuis une navigation normale.",
])
