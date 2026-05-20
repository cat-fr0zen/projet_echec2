<?php

declare(strict_types=1);

require_once __DIR__ . '/../MVC/modeles/StockageJson.php';
require_once __DIR__ . '/../MVC/modeles/DepotHoraires.php';

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[ERREUR] {$message}" . PHP_EOL);
        exit(1);
    }
}

$fichierTemporaire = __DIR__ . '/tmp_club_schedule.json';

if (file_exists($fichierTemporaire)) {
    unlink($fichierTemporaire);
}

$stockage = new StockageJson($fichierTemporaire);
$depot = new DepotHoraires($stockage);

$defaut = $depot->obtenir();

verifier(($defaut['season_label'] ?? '') !== '', 'Les horaires par defaut doivent avoir un titre.');
verifier(count($defaut['items'] ?? []) >= 5, 'Les horaires par defaut doivent contenir les creneaux principaux.');

$succes = $depot->mettreAJour(
    'Horaires test',
    '<strong>Fermeture exceptionnelle</strong>',
    [
        [
            'day' => 'Mardi',
            'time' => '18h00 à 19h30',
            'title' => '<script>alert(1)</script>Entraînement',
            'details' => "Avec Patrick.\nSalle principale.",
            'is_holiday' => false,
        ],
        [
            'day' => 'Jour férié',
            'time' => 'Fermé',
            'title' => 'Fermeture',
            'details' => 'Pas de séance.',
            'is_holiday' => true,
        ],
    ]
);

assert($succes === true);

$horaire = $depot->obtenir();
$resume = $depot->resumerParJour();

verifier($horaire['season_label'] === 'Horaires test', 'Le titre public doit etre modifiable.');
verifier($horaire['holiday_notice'] === 'Fermeture exceptionnelle', 'Le message jour ferie doit filtrer le HTML.');
verifier($horaire['items'][0]['title'] === 'alert(1)Entraînement', 'Le titre du creneau doit filtrer le HTML.');
verifier($horaire['items'][1]['is_holiday'] === true, 'Un creneau doit pouvoir etre marque jour ferie.');
verifier($resume[0]['day'] === 'Mardi', 'Le resume doit conserver le jour.');
verifier($resume[1]['has_holiday'] === true, 'Le resume doit remonter le statut jour ferie.');

if (file_exists($fichierTemporaire)) {
    unlink($fichierTemporaire);
}

echo "Horaires club OK.\n";
