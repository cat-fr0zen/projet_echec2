@include('errors._http-status', [
    'code' => 423,
    'label' => 'Erreur 423',
    'title' => 'La ressource est verrouillee.',
    'message' => "Cette ressource est temporairement indisponible pour modification.",
    'hint' => "Patiente un instant puis reessaie.",
])
