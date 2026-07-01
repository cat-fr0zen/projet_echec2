@include('errors._http-status', [
    'code' => 502,
    'label' => 'Erreur 502',
    'title' => 'La passerelle a cafouille.',
    'message' => "Le serveur a recu une reponse invalide d'un service amont.",
    'hint' => "Patiente quelques instants avant de recharger.",
])
