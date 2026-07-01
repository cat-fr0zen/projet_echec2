@include('errors._http-status', [
    'code' => 405,
    'label' => 'Erreur 405',
    'title' => "Ce coup n'est pas autorise.",
    'message' => "La methode utilisee n'est pas acceptee pour cette page.",
    'hint' => "Recharge la page puis repasse par les boutons normaux du site.",
])
