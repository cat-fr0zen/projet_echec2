@include('errors._http-status', [
    'code' => 503,
    'label' => 'Erreur 503',
    'title' => 'Le site fait une courte pause.',
    'message' => "Le service est temporairement indisponible, souvent pour maintenance ou surcharge.",
    'hint' => "Reviens dans quelques minutes.",
])
