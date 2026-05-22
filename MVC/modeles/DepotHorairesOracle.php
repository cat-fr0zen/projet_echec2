<?php

declare(strict_types=1);

final class DepotHorairesOracle
{
    private const IDENTIFIANT = 'club_schedule';
    private const MAX_ITEMS = 16;

    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

    public function obtenir(): array
    {
        $horaire = $this->base->lireUne(
            'SELECT
                schedule_id,
                season_label,
                holiday_notice,
                TO_CHAR(updated_at, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') updated_at
            FROM horaire_club
            WHERE schedule_id = :schedule_id',
            ['schedule_id' => self::IDENTIFIANT]
        );

        if ($horaire === null) {
            return $this->normaliserHoraire($this->horairesParDefaut());
        }

        $items = $this->base->lireTout(
            'SELECT jour, horaire, titre, details, jour_ferie
            FROM horaire_creneau
            WHERE schedule_id = :schedule_id
            ORDER BY ordre_affichage',
            ['schedule_id' => self::IDENTIFIANT]
        );

        $horaire['items'] = array_map(static fn (array $item): array => [
            'day' => (string) ($item['jour'] ?? ''),
            'time' => (string) ($item['horaire'] ?? ''),
            'title' => (string) ($item['titre'] ?? ''),
            'details' => (string) ($item['details'] ?? ''),
            'is_holiday' => (int) ($item['jour_ferie'] ?? 0) === 1,
        ], $items);

        return $this->normaliserHoraire($horaire);
    }

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

        $seasonLabel = $this->nettoyerTexteCourt($libelleSaison, 120) ?: 'Horaires du club';
        $holidayNotice = $this->nettoyerTexteLong($messageJourFerie, 320);

        $this->base->transaction(function () use ($seasonLabel, $holidayNotice, $items): void {
            $lignes = $this->base->executer(
                'UPDATE horaire_club
                    SET season_label = :season_label,
                        holiday_notice = :holiday_notice,
                        updated_at = SYSDATE
                  WHERE schedule_id = :schedule_id',
                [
                    'season_label' => $seasonLabel,
                    'holiday_notice' => $holidayNotice,
                    'schedule_id' => self::IDENTIFIANT,
                ]
            );

            if ($lignes === 0) {
                $this->base->executer(
                    'INSERT INTO horaire_club (schedule_id, season_label, holiday_notice, updated_at)
                    VALUES (:schedule_id, :season_label, :holiday_notice, SYSDATE)',
                    [
                        'schedule_id' => self::IDENTIFIANT,
                        'season_label' => $seasonLabel,
                        'holiday_notice' => $holidayNotice,
                    ]
                );
            }

            $this->base->executer(
                'DELETE FROM horaire_creneau WHERE schedule_id = :schedule_id',
                ['schedule_id' => self::IDENTIFIANT]
            );

            foreach ($items as $index => $item) {
                $this->base->executer(
                    'INSERT INTO horaire_creneau (
                        identifiant_creneau, schedule_id, ordre_affichage,
                        jour, horaire, titre, details, jour_ferie
                    ) VALUES (
                        :identifiant_creneau, :schedule_id, :ordre_affichage,
                        :jour, :horaire, :titre, :details, :jour_ferie
                    )',
                    [
                        'identifiant_creneau' => 'horaire_' . bin2hex(random_bytes(8)),
                        'schedule_id' => self::IDENTIFIANT,
                        'ordre_affichage' => $index + 1,
                        'jour' => $item['day'],
                        'horaire' => $item['time'],
                        'titre' => $item['title'],
                        'details' => $item['details'],
                        'jour_ferie' => $item['is_holiday'] ? 1 : 0,
                    ]
                );
            }
        });

        return true;
    }

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
        $items = [];

        foreach (array_slice((array) ($horaire['items'] ?? []), 0, self::MAX_ITEMS) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $day = $this->nettoyerTexteCourt((string) ($item['day'] ?? $item['jour'] ?? ''), 60);
            $time = $this->nettoyerTexteCourt((string) ($item['time'] ?? $item['horaire'] ?? ''), 80);

            if ($day === '' || $time === '') {
                continue;
            }

            $details = $this->nettoyerTexteLong((string) ($item['details'] ?? ''), 1400);

            $items[] = [
                'day' => $day,
                'time' => $time,
                'title' => $this->nettoyerTexteCourt((string) ($item['title'] ?? $item['titre'] ?? 'Activite du club'), 180),
                'details' => $details,
                'details_lines' => $this->extraireLignes($details),
                'is_holiday' => (bool) ($item['is_holiday'] ?? $item['jour_ferie'] ?? false),
            ];
        }

        if ($items === []) {
            return $this->normaliserHoraire($this->horairesParDefaut());
        }

        return [
            'schedule_id' => self::IDENTIFIANT,
            'season_label' => $this->nettoyerTexteCourt((string) ($horaire['season_label'] ?? 'Horaires du club'), 120),
            'holiday_notice' => $this->nettoyerTexteLong((string) ($horaire['holiday_notice'] ?? ''), 320),
            'updated_at' => $this->nettoyerTexteCourt((string) ($horaire['updated_at'] ?? ''), 40),
            'items' => $items,
        ];
    }

    private function horairesParDefaut(): array
    {
        return [
            'schedule_id' => self::IDENTIFIANT,
            'season_label' => "Horaires 2025/2026 - Club d'Echecs",
            'holiday_notice' => "Les horaires peuvent etre adaptes ou le club ferme les jours feries.",
            'updated_at' => gmdate('c'),
            'items' => [
                $this->creneau('Mardi', '18h00 a 19h30', 'Entrainement ados et adultes debutants', 'Avec Patrick.'),
                $this->creneau('Mercredi', '17h30 a 18h30', 'Initiation et perfectionnement enfants debutants', 'Avec Ashot et Francois.'),
                $this->creneau('Jeudi', '14h00 a 16h30', 'Club Senior +', 'Avec Francois.'),
                $this->creneau('Vendredi', '17h00 a 18h00', 'Cours de preparation aux championnats scolaires', "Ecole Sainte-Marie et College Saint-Pierre.\nAvec Jean-Patrick a l'ecole et Ryan au college."),
                $this->creneau('Vendredi', '18h00 a 19h30', 'Parties libres tous publics', "Materiel a disposition.\nSalle du restaurant du Cafe des images."),
                $this->creneau('Samedi', '10h30 a 12h00', "Groupes selon l'age et le niveau", "Centre Socioculturel CAF, 202 Boulevard des Belles Portes, 14200 Herouville Saint-Clair."),
                $this->creneau('Samedi', '14h30 a 16h00', 'Tous publics', 'Parties libres et tournois mensuels au club.'),
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
        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }

    private function extraireLignes(string $valeur): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", $valeur)),
            static fn (string $ligne): bool => $ligne !== ''
        ));
    }
}
