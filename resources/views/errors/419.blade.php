@include('errors._http-status', [
    'code' => 419,
    'label' => 'Erreur 419',
    'title' => 'La session a expire.',
    'message' => "Pour des raisons de securite, la session ou le formulaire n'est plus valide.",
    'hint' => "Recharge la page et renvoie a nouveau le formulaire.",
])
