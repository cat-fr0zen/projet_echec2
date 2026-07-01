@include('errors._http-status', [
    'code' => 414,
    'label' => 'Erreur 414',
    'title' => 'Adresse trop longue.',
    'message' => "L'URL demandee est trop longue pour etre traitee.",
    'hint' => "Repars depuis l'accueil plutot que depuis un lien incomplet.",
])
