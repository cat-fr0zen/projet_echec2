@include('errors._http-status', [
    'code' => 401,
    'label' => 'Erreur 401',
    'title' => 'La partie demande une connexion.',
    'message' => "Tu dois etre authentifie pour acceder a cette ressource.",
    'hint' => "Reconnecte-toi puis reessaie.",
])
