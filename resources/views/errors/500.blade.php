@include('errors._http-status', [
    'code' => 500,
    'label' => 'Erreur 500',
    'title' => 'Le roi est tombe sur une erreur serveur.',
    'message' => "Une erreur interne est survenue pendant le traitement de la page.",
    'hint' => "Reessaie plus tard. Si le probleme persiste, contacte le club.",
])
