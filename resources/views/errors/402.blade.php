@include('errors._http-status', [
    'code' => 402,
    'label' => 'Erreur 402',
    'title' => 'Cette operation ne peut pas etre finalisee.',
    'message' => "Le serveur a refuse cette requete pour une raison liee au paiement ou a l'operation demandee.",
    'hint' => "Retourne a l'accueil ou contacte le club si tu pensais cette action disponible.",
])
