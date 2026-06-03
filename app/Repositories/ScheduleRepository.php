<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class ScheduleRepository
{
    private const IDENTIFIANT = 'club_schedule';
    private const MAX_ITEMS = 16;

    public function obtenir(): array
    {
        $schedule = DB::table('horaire_club')
            ->where('schedule_id', self::IDENTIFIANT)
            ->first();

        if ($schedule === null) {
            return $this->normaliserHoraire($this->horairesParDefaut());
        }

        $items = DB::table('horaire_creneau')
            ->where('schedule_id', self::IDENTIFIANT)
            ->orderBy('ordre_affichage')
            ->get()
            ->all();

        return $this->normaliserHoraire([
            'schedule_id' => $schedule->schedule_id,
            'season_label' => $schedule->season_label,
            'holiday_notice' => $schedule->holiday_notice,
            'updated_at' => $schedule->updated_at,
            'items' => array_map(static fn (object $item): array => [
                'day' => (string) ($item->jour ?? ''),
                'time' => self::formaterPlageHoraire(
                    $item->heure_debut ?? null,
                    $item->heure_fin ?? null,
                    $item->horaire ?? null
                ),
                'title' => (string) ($item->titre ?? ''),
                'details' => (string) ($item->details ?? ''),
                'is_holiday' => (int) ($item->jour_ferie ?? 0) === 1,
            ], $items),
        ]);
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

            [$heureDebut, $heureFin] = $this->decomposerPlageHoraire($time);
            if ($heureDebut === null || $heureFin === null) {
                continue;
            }

            $items[] = [
                'day' => $day,
                'time' => $time,
                'heure_debut' => $heureDebut,
                'heure_fin' => $heureFin,
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

        DB::transaction(function () use ($seasonLabel, $holidayNotice, $items): void {
            DB::table('horaire_club')->updateOrInsert(
                ['schedule_id' => self::IDENTIFIANT],
                [
                    'season_label' => $seasonLabel,
                    'holiday_notice' => $holidayNotice,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            DB::table('horaire_creneau')->where('schedule_id', self::IDENTIFIANT)->delete();

            foreach ($items as $index => $item) {
                DB::table('horaire_creneau')->insert([
                    'identifiant_creneau' => 'horaire_' . bin2hex(random_bytes(8)),
                    'schedule_id' => self::IDENTIFIANT,
                    'ordre_affichage' => $index + 1,
                    'jour' => $item['day'],
                    'heure_debut' => $item['heure_debut'],
                    'heure_fin' => $item['heure_fin'],
                    'titre' => $item['title'],
                    'details' => $item['details'],
                    'jour_ferie' => $item['is_holiday'] ? 1 : 0,
                ]);
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
            $time = $this->nettoyerTexteCourt((string) ($item['time'] ?? ''), 80);

            if ($time === '') {
                $time = self::formaterPlageHoraire(
                    $item['heure_debut'] ?? null,
                    $item['heure_fin'] ?? null,
                    $item['horaire'] ?? null
                );
            }

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
            'updated_at' => (string) ($horaire['updated_at'] ?? ''),
            'items' => $items,
        ];
    }

    private function horairesParDefaut(): array
    {
        return [
            'schedule_id' => self::IDENTIFIANT,
            'season_label' => "Horaires 2025/2026 - Club d'Echecs",
            'holiday_notice' => 'Les horaires peuvent etre adaptes ou le club ferme les jours feries.',
            'updated_at' => gmdate('c'),
            'items' => [
                $this->creneau('Mardi', '18h00 a 19h30', 'Entrainement ados et adultes debutants', 'Avec Patrick.'),
                $this->creneau('Mercredi', '17h30 a 18h30', 'Initiation et perfectionnement enfants debutants', 'Avec Ashot et Francois.'),
                $this->creneau('Jeudi', '14h00 a 16h30', 'Club Senior +', 'Avec Francois.'),
                $this->creneau('Vendredi', '17h00 a 18h00', 'Cours de preparation aux championnats scolaires', "Ecole Sainte-Marie et College Saint-Pierre.\nAvec Jean-Patrick a l'ecole et Ryan au college."),
                $this->creneau('Vendredi', '18h00 a 19h30', 'Parties libres tous publics', "Materiel a disposition.\nSalle du restaurant du Cafe des images."),
                $this->creneau('Samedi', '10h30 a 12h00', "Groupes selon l'age et le niveau", 'Centre Socioculturel CAF, 202 Boulevard des Belles Portes, 14200 Herouville Saint-Clair.'),
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

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function decomposerPlageHoraire(string $plage): array
    {
        if (preg_match('/(\d{1,2})[h:](\d{2})\s*a\s*(\d{1,2})[h:](\d{2})/i', $plage, $captures) !== 1) {
            return [null, null];
        }

        return [
            sprintf('%02d:%02d:00', (int) $captures[1], (int) $captures[2]),
            sprintf('%02d:%02d:00', (int) $captures[3], (int) $captures[4]),
        ];
    }

    private static function formaterPlageHoraire(mixed $heureDebut, mixed $heureFin, mixed $fallback = null): string
    {
        $heureDebut = trim((string) ($heureDebut ?? ''));
        $heureFin = trim((string) ($heureFin ?? ''));

        if ($heureDebut !== '' && $heureFin !== '' && strlen($heureDebut) >= 5 && strlen($heureFin) >= 5) {
            return sprintf(
                '%sh%s a %sh%s',
                substr($heureDebut, 0, 2),
                substr($heureDebut, 3, 2),
                substr($heureFin, 0, 2),
                substr($heureFin, 3, 2)
            );
        }

        return trim((string) ($fallback ?? ''));
    }

    private function nettoyerTexteCourt(string $valeur, int $limite): string
    {
        $valeur = trim(preg_replace('/\s+/', ' ', $valeur) ?? '');

        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }

    private function nettoyerTexteLong(string $valeur, int $limite): string
    {
        $valeur = trim(preg_replace("/\r\n?/", "\n", $valeur) ?? '');

        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }

    private function extraireLignes(string $details): array
    {
        if ($details === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\n+/', $details) ?: []),
            static fn (string $line): bool => $line !== ''
        ));
    }
}
