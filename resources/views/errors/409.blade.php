@include('errors._http-status', [
    'code' => 409,
    'label' => 'Erreur 409',
    'title' => 'Conflit detecte.',
    'message' => "Cette action entre en conflit avec l'etat actuel des donnees.",
    'hint' => "Actualise la page avant de recommencer.",
])
