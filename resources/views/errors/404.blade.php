@include('errors._http-status', [
    'code' => 404,
    'label' => 'Erreur 404',
    'title' => 'Vous etes en echec &amp; mat.',
    'message' => "La page demandee n'existe pas, a change d'adresse ou n'est plus accessible.",
    'hint' => "Reviens a l'accueil ou passe par le menu principal pour retrouver la bonne page.",
])
