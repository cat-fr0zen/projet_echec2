<?php

declare(strict_types=1);

/**
 * DepotHoraires
 *
 * Gere les horaires publics du club:
 * - affichage court sur l'accueil
 * - emploi du temps complet
 * - edition par l'administrateur via stockage JSON
 */
final class DepotHoraires
{
    private const IDENTIFIANT = 'club_schedule';
    private const MAX_ITEMS = 16;

    public function __construct(private StockageJson $stockage)
    {
    }

    /**
     * Retourne les horaires normalises, ou les horaires par defaut si le JSON est vide.
     *
     * @return array Donnees publiques des horaires.
     */
    public function obtenir(): array
    {
        $donnees = $this->stockage->lire();
        $premiereEntree = is_array($donnees[0] ?? null) ? $donnees[0] : [];

        return $this->normaliserHoraire($premiereEntree);
    }

    /**
     * Enregistre les horaires depuis le formulaire admin.
     *
     * @param string $libelleSaison Titre public.
     * @param string $messageJourFerie Message d'exception visible publiquement.
     * @param array $creneaux Lignes saisies par l'admin.
     * @return bool True si au moins un creneau valide a ete conserve.
     */
    public function mettreAJour(string $libelleSaison, string $messageJourFerie, array $creneaux): bool
    {
        $items = [];

        foreach (array_slice($creneaux, 0, self::MAX_ITEMS) as $creneau) {
            if (!is_array($creneau)) {
                continue;
            }

            $day = $this->nettoyerTexteCourt((string) ($creneau['day'] ?? ''), 60);
            $time = $this->nettoyerTexteCourt((string) ($creneau['time'] ?? ''), 80);
            $title = $this->nettoyerTexteCourt((string) ($creneau['title'] ?? ''), 180);
            $details = $this->nettoyerTexteLong((string) ($creneau['details'] ?? ''), 1400);

            if ($day === '' && $time === '' && $title === '' && $details === '') {
                continue;
            }

            if ($day === '' || $time === '') {
                continue;
            }

            $items[] = [
                'day' => $day,
                'time' => $time,
                'title' => $title !== '' ? $title : 'Activite du club',
                'details' => $details,
                'is_holiday' => (bool) ($creneau['is_holiday'] ?? false),
            ];
        }

        if ($items === []) {
            return false;
        }

        $horaire = $this->normaliserHoraire([
            'schedule_id' => self::IDENTIFIANT,
            'season_label' => $this->nettoyerTexteCourt($libelleSaison, 120),
            'holiday_notice' => $this->nettoyerTexteLong($messageJourFerie, 320),
            'updated_at' => gmdate('c'),
            'items' => $items,
        ]);

        $this->stockage->ecrire([$horaire]);

        return true;
    }

    /**
     * Retourne une version groupee par jour pour l'affichage court.
     *
     * @return array<int, array{day: string, times: string, has_holiday: bool}>
     */
    public function resumerParJour(): array
    {
        $resume = [];

        foreach ($this->obtenir()['items'] as $item) {
            $day = (string) ($item['day'] ?? '');
            $time = (string) ($item['time'] ?? '');

            if ($day === '' || $time === '') {
                continue;
            }

            if (!isset($resume[$day])) {
                $resume[$day] = [
                    'day' => $day,
                    'times' => [],
                    'has_holiday' => false,
                ];
            }

            if (!in_array($time, $resume[$day]['times'], true)) {
                $resume[$day]['times'][] = $time;
            }

            $resume[$day]['has_holiday'] = $resume[$day]['has_holiday'] || (bool) ($item['is_holiday'] ?? false);
        }

        return array_map(
            static fn (array $entry): array => [
                'day' => (string) $entry['day'],
                'times' => implode(' / ', $entry['times']),
                'has_holiday' => (bool) $entry['has_holiday'],
            ],
            array_values($resume)
        );
    }

    /**
     * Adapte les horaires au format historique utilise par la page Club.
     *
     * @return array<int, array{day: string, slot: string, title: string, text: string}>
     */
    public function adapterPlanning(): array
    {
        return array_map(
            static fn (array $item): array => [
                'day' => (string) ($item['day'] ?? ''),
                'slot' => (string) ($item['time'] ?? ''),
                'title' => (string) ($item['title'] ?? ''),
                'text' => (string) ($item['details'] ?? ''),
            ],
            $this->obtenir()['items']
        );
    }

    private function normaliserHoraire(array $horaire): array
    {
        $horaire = $horaire !== [] ? $horaire : $this->horairesParDefaut();
        $items = [];

        foreach (array_slice((array) ($horaire['items'] ?? []), 0, self::MAX_ITEMS) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $day = $this->nettoyerTexteCourt((string) ($item['day'] ?? ''), 60);
            $time = $this->nettoyerTexteCourt((string) ($item['time'] ?? ''), 80);

            if ($day === '' || $time === '') {
                continue;
            }

            $details = $this->nettoyerTexteLong((string) ($item['details'] ?? ''), 1400);

            $items[] = [
                'day' => $day,
                'time' => $time,
                'title' => $this->nettoyerTexteCourt((string) ($item['title'] ?? 'Activite du club'), 180),
                'details' => $details,
                'details_lines' => $this->extraireLignes($details),
                'is_holiday' => (bool) ($item['is_holiday'] ?? false),
            ];
        }

        if ($items === []) {
            return $this->normaliserHoraire($this->horairesParDefaut());
        }

        return [
            'schedule_id' => self::IDENTIFIANT,
            'season_label' => $this->nettoyerTexteCourt((string) ($horaire['season_label'] ?? ''), 120) ?: 'Horaires du club',
            'holiday_notice' => $this->nettoyerTexteLong((string) ($horaire['holiday_notice'] ?? ''), 320),
            'updated_at' => $this->nettoyerTexteCourt((string) ($horaire['updated_at'] ?? ''), 40),
            'items' => $items,
        ];
    }

    private function horairesParDefaut(): array
    {
        return [
            'schedule_id' => self::IDENTIFIANT,
            'season_label' => 'Horaires 2025/2026 - Club d’Échecs',
            'holiday_notice' => "Les horaires peuvent être adaptés ou le club fermé. Consultez l'emploi du temps complet avant de vous déplacer.",
            'updated_at' => gmdate('c'),
            'items' => [
                $this->creneau('Mardi', '18h00 à 19h30', 'Entraînement ados et adultes débutants', 'Avec Patrick.'),
                $this->creneau('Mercredi', '17h30 à 18h30', 'Initiation et perfectionnement enfants débutants', 'Avec Ashot et François.'),
                $this->creneau('Jeudi', '14h00 à 16h30', 'Club Senior +', 'Avec François.'),
                $this->creneau('Vendredi', '17h00 à 18h00', 'Cours de préparation aux championnats scolaires', "École Sainte-Marie et Collège Saint-Pierre.\nAvec Jean-Patrick à l'école et Ryan au collège."),
                $this->creneau('Vendredi', '18h00 à 19h30', 'Parties libres tous publics', "Matériel à disposition.\nSalle du restaurant du Café des images.\nPrésence ponctuelle possible de bénévoles du club."),
                $this->creneau('Samedi', '10h30 à 12h00', "Groupes selon l'âge et le niveau", "Centre Socioculturel CAF, 202 Boulevard des Belles Portes, 14200 Hérouville Saint-Clair.\nPetits cavaliers (4-7 ans) avec Marlène.\nEnfants débutants (8-10 ans) avec Silvain.\nPerfectionnement jeunes (11-13 ans) avec Tanguy.\nMaîtrise enfants et jeunes (10-16 ans) avec Mikayel.\nPerfectionnement ados et adultes avec Jean-Patrick.\nNouvel entraînement adultes.\nUne fois par mois: entraînement avec un Maître International."),
                $this->creneau('Samedi', '14h30 à 16h00', 'Tous publics', "Parties libres et tournois mensuels au club avec Frédéric.\nEnfants débutants et intermédiaires avec Ryan.\nUne fois par mois: animation double-jeu à La Passerelle."),
            ],
        ];
    }

    private function creneau(string $day, string $time, string $title, string $details, bool $isHoliday = false): array
    {
        return [
            'day' => $day,
            'time' => $time,
            'title' => $title,
            'details' => $details,
            'is_holiday' => $isHoliday,
        ];
    }

    private function nettoyerTexteCourt(string $valeur, int $limite): string
    {
        $valeur = trim(strip_tags($valeur));
        $valeur = preg_replace('/\s+/u', ' ', $valeur) ?? '';

        return $this->limiter($valeur, $limite);
    }

    private function nettoyerTexteLong(string $valeur, int $limite): string
    {
        $valeur = str_replace(["\r\n", "\r"], "\n", strip_tags($valeur));
        $valeur = preg_replace("/[ \t]+/u", ' ', $valeur) ?? '';
        $valeur = preg_replace("/\n{3,}/u", "\n\n", $valeur) ?? '';

        return $this->limiter(trim($valeur), $limite);
    }

    private function limiter(string $valeur, int $limite): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($valeur, 0, $limite);
        }

        return substr($valeur, 0, $limite);
    }

    /**
     * @return array<int, string>
     */
    private function extraireLignes(string $valeur): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", $valeur)),
            static fn (string $ligne): bool => $ligne !== ''
        ));
    }
}
