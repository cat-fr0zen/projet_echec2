@include('errors._http-status', [
    'code' => 429,
    'label' => 'Erreur 429',
    'title' => 'Trop de tentatives.',
    'message' => "Le serveur a limite temporairement les requetes pour proteger le site.",
    'hint' => "Attends quelques minutes avant de recommencer.",
])
