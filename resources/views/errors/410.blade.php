@include('errors._http-status', [
    'code' => 410,
    'label' => 'Erreur 410',
    'title' => "Cette page a quitte l'echiquier.",
    'message' => "La ressource demandee n'est plus disponible de facon definitive.",
    'hint' => "Utilise la navigation du site pour trouver la version a jour.",
])
