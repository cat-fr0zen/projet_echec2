@include('errors._http-status', [
    'code' => 504,
    'label' => 'Erreur 504',
    'title' => 'Le temps de la passerelle est depasse.',
    'message' => "Un service externe a mis trop de temps a repondre.",
    'hint' => "Reessaie un peu plus tard.",
])
