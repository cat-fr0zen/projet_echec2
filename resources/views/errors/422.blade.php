@include('errors._http-status', [
    'code' => 422,
    'label' => 'Erreur 422',
    'title' => "Le coup n'est pas valable.",
    'message' => "Certaines donnees envoyees sont invalides ou incompletes.",
    'hint' => "Verifie les champs du formulaire puis corrige ce qui manque.",
])
