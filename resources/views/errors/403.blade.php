@include('errors._http-status', [
    'code' => 403,
    'label' => 'Erreur 403',
    'title' => 'Acces refuse.',
    'message' => "Tu n'as pas les droits necessaires pour ouvrir cette page.",
    'hint' => "Verifie ton compte ou contacte un administrateur du club.",
])
