@include('errors._http-status', [
    'code' => 408,
    'label' => 'Erreur 408',
    'title' => 'Le temps est ecoule.',
    'message' => "Le serveur a attendu trop longtemps la fin de la requete.",
    'hint' => "Reessaie dans quelques instants.",
])
