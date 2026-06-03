<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DammierPuzzleSeeder extends Seeder
{
    public function run(): void
    {
        $puzzles = $this->genererPuzzles();

        if (count($puzzles) < 100) {
            throw new \RuntimeException('Le pool de puzzles dammier doit contenir au moins 100 entrees.');
        }

        DB::transaction(function () use ($puzzles): void {
            DB::table('dammier_puzzle')->update(['actif' => false]);

            foreach ($puzzles as $puzzle) {
                DB::table('dammier_puzzle')->updateOrInsert(
                    ['dammier_id' => $puzzle['id']],
                    [
                        'titre' => $puzzle['titre'],
                        'description' => $puzzle['description'],
                        'instruction' => $puzzle['instruction'],
                        'fen' => $puzzle['fen'],
                        'trait' => 'w',
                        'source_puzzle' => 'ouverture_reelle',
                        'code_difficulte' => $puzzle['code_difficulte'],
                        'actif' => true,
                        'cree_le' => now(),
                    ]
                );

                DB::table('dammier_solution_etape')->where('dammier_puzzle_id', $puzzle['id'])->delete();
                DB::table('dammier_reponse_attendue')->where('dammier_puzzle_id', $puzzle['id'])->delete();
                DB::table('dammier_indice')->where('dammier_puzzle_id', $puzzle['id'])->delete();

                foreach ($puzzle['solution'] as $index => $coup) {
                    DB::table('dammier_solution_etape')->insert([
                        'identifiant_etape' => sprintf('%s_solution_%02d', $puzzle['id'], $index + 1),
                        'dammier_puzzle_id' => $puzzle['id'],
                        'ordre_etape' => $index + 1,
                        'coup' => $coup,
                    ]);
                }

                foreach ($puzzle['reponses'] as $index => $coup) {
                    DB::table('dammier_reponse_attendue')->insert([
                        'identifiant_reponse' => sprintf('%s_reponse_%02d', $puzzle['id'], $index + 1),
                        'dammier_puzzle_id' => $puzzle['id'],
                        'ordre_reponse' => $index + 1,
                        'coup' => $coup,
                    ]);
                }

                foreach ($puzzle['indices'] as $index => $indice) {
                    DB::table('dammier_indice')->insert([
                        'identifiant_indice' => sprintf('%s_indice_%02d', $puzzle['id'], $index + 1),
                        'dammier_puzzle_id' => $puzzle['id'],
                        'ordre_indice' => $index + 1,
                        'texte_indice' => $indice,
                    ]);
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function genererPuzzles(): array
    {
        $puzzles = [];
        $indexGlobal = 1;

        foreach ($this->ouverturesReelles() as $ouverture) {
            $variantIndex = 1;
            $totalCoups = count($ouverture['moves']);

            foreach ([8, 6, 4] as $tailleFenetre) {
                for ($depart = 0; $depart <= $totalCoups - $tailleFenetre; $depart += 2) {
                    $fenetre = array_slice($ouverture['moves'], $depart, $tailleFenetre);

                    if ($this->sequenceContientCoupNonSupporte($fenetre)) {
                        continue;
                    }

                    $solution = [];
                    $reponses = [];

                    foreach ($fenetre as $position => $coup) {
                        if ($position % 2 === 0) {
                            $solution[] = $coup;
                            continue;
                        }

                        $reponses[] = $coup;
                    }

                    $puzzles[] = [
                        'id' => sprintf('dammier_%s_%03d', $ouverture['slug'], $indexGlobal),
                        'titre' => sprintf('%s - suite %02d', $ouverture['name'], $variantIndex),
                        'description' => sprintf(
                            "Continue une ligne reelle de %s en %d coups blancs.",
                            $ouverture['name'],
                            count($solution)
                        ),
                        'instruction' => sprintf(
                            'Trouve la meilleure continuation theorique. %d coups blancs a jouer.',
                            count($solution)
                        ),
                        'fen' => $this->genererFenApresPrefix(array_slice($ouverture['moves'], 0, $depart)),
                        'code_difficulte' => $this->determinerDifficulte($depart, count($solution)),
                        'solution' => $solution,
                        'reponses' => $reponses,
                        'indices' => $this->construireIndices($ouverture['hint'], $ouverture['theme'], count($solution)),
                    ];

                    $variantIndex += 1;
                    $indexGlobal += 1;
                }
            }
        }

        return $puzzles;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ouverturesReelles(): array
    {
        return [
            [
                'slug' => 'italienne',
                'name' => 'Ouverture italienne',
                'theme' => 'Developpement rapide et pression sur f7.',
                'hint' => 'Les pieces legeres sortent vite et le centre reste sous tension.',
                'moves' => [
                    'e2e4', 'e7e5', 'g1f3', 'b8c6', 'f1c4', 'f8c5', 'c2c3', 'g8f6',
                    'd2d4', 'e5d4', 'c3d4', 'c5b4', 'b1c3', 'f6e4', 'e1g1', 'e4c3',
                    'b2c3', 'b4c3', 'c1a3', 'd7d6',
                ],
            ],
            [
                'slug' => 'ruy_lopez',
                'name' => 'Defense espagnole',
                'theme' => 'Pression positionnelle et lutte pour le centre.',
                'hint' => 'La logique est de developper sans perdre la pression sur e5.',
                'moves' => [
                    'e2e4', 'e7e5', 'g1f3', 'b8c6', 'f1b5', 'a7a6', 'b5a4', 'g8f6',
                    'e1g1', 'f8e7', 'f1e1', 'b7b5', 'a4b3', 'd7d6', 'c2c3', 'e8g8',
                    'h2h3', 'c8b7', 'd2d4', 'f8e8',
                ],
            ],
            [
                'slug' => 'sicilienne',
                'name' => 'Defense sicilienne',
                'theme' => 'Desiquilibre immediat et activite des pieces.',
                'hint' => 'Cherche les coups les plus naturels contre la structure sicilienne.',
                'moves' => [
                    'e2e4', 'c7c5', 'g1f3', 'd7d6', 'd2d4', 'c5d4', 'f3d4', 'g8f6',
                    'b1c3', 'a7a6', 'c1g5', 'e7e6', 'f2f4', 'b8c6', 'd4f3', 'f8e7',
                    'f1d3', 'e8g8', 'e1g1', 'h7h6',
                ],
            ],
            [
                'slug' => 'francaise',
                'name' => 'Defense francaise avancee',
                'theme' => 'Espace blanc contre contre-jeu sur le centre.',
                'hint' => 'Les Blancs gardent l espace puis developpent derriere leur chaine de pions.',
                'moves' => [
                    'e2e4', 'e7e6', 'd2d4', 'd7d5', 'e4e5', 'c7c5', 'c2c3', 'b8c6',
                    'g1f3', 'c5d4', 'c3d4', 'c8d7', 'b1c3', 'g8e7', 'c1e3', 'e7f5',
                    'f1d3', 'f8e7', 'e1g1', 'h7h5',
                ],
            ],
            [
                'slug' => 'caro_kann',
                'name' => 'Defense Caro-Kann',
                'theme' => 'Structure saine, developpement harmonieux et pions solides.',
                'hint' => 'Observe la logique de developpement des deux camps sans sacrifier le centre.',
                'moves' => [
                    'e2e4', 'c7c6', 'd2d4', 'd7d5', 'b1c3', 'd5e4', 'c3e4', 'c8f5',
                    'e4g3', 'f5g6', 'h2h4', 'h7h6', 'g1f3', 'b8d7', 'h4h5', 'g6h7',
                    'f1d3', 'h7d3', 'd1d3', 'e7e6',
                ],
            ],
            [
                'slug' => 'qgd',
                'name' => 'Gambit dame refuse',
                'theme' => 'Coordination lente et controle des cases cles.',
                'hint' => 'Le centre doit rester stable pendant que les pieces prennent leurs meilleures cases.',
                'moves' => [
                    'd2d4', 'd7d5', 'c2c4', 'e7e6', 'b1c3', 'g8f6', 'c1g5', 'f8e7',
                    'e2e3', 'e8g8', 'g1f3', 'b8d7', 'a1c1', 'c7c6', 'f1d3', 'd5c4',
                    'd3c4', 'f6d5', 'g5e7', 'd8e7',
                ],
            ],
            [
                'slug' => 'slave',
                'name' => 'Defense slave',
                'theme' => 'Solidite noire et recuperation du pion avec activite.',
                'hint' => 'Les Blancs cherchent surtout un developpement propre avant de recuperer du materiel.',
                'moves' => [
                    'd2d4', 'd7d5', 'c2c4', 'c7c6', 'g1f3', 'g8f6', 'b1c3', 'd5c4',
                    'a2a4', 'c8f5', 'e2e3', 'e7e6', 'f1c4', 'b8d7', 'e1g1', 'f8b4',
                    'd1e2', 'e8g8', 'e3e4', 'f5g6',
                ],
            ],
            [
                'slug' => 'anglaise',
                'name' => 'Ouverture anglaise',
                'theme' => 'Contre-attaque centrale avec fianchetto et jeu flexible.',
                'hint' => 'Le plan repose sur un controle souple du centre et un developpement sans faiblesse.',
                'moves' => [
                    'c2c4', 'e7e5', 'b1c3', 'g8f6', 'g2g3', 'd7d5', 'c4d5', 'f6d5',
                    'f1g2', 'd5c3', 'b2c3', 'b8c6', 'g1f3', 'f8e7', 'e1g1', 'e8g8',
                    'd2d3', 'f7f5', 'c1b2', 'e7f6',
                ],
            ],
            [
                'slug' => 'londres',
                'name' => 'Systeme de Londres',
                'theme' => 'Developpement simple, structure saine et pression graduelle.',
                'hint' => 'Les coups blancs cherchent surtout des cases naturelles et une structure solide.',
                'moves' => [
                    'd2d4', 'd7d5', 'g1f3', 'g8f6', 'c1f4', 'c7c5', 'e2e3', 'b8c6',
                    'b1d2', 'c8f5', 'c2c3', 'e7e6', 'f1d3', 'f5d3', 'd1d3', 'f8d6',
                    'd4c5', 'd6c5', 'e1g1', 'e8g8',
                ],
            ],
            [
                'slug' => 'kia',
                'name' => 'Attaque indienne du roi',
                'theme' => 'Fianchetto, roque rapide et frappe centrale retardee.',
                'hint' => 'Le plan blanc est flexible : mise en place puis contre-jeu central.',
                'moves' => [
                    'g1f3', 'd7d5', 'g2g3', 'c7c6', 'f1g2', 'c8g4', 'e1g1', 'b8d7',
                    'd2d3', 'e7e5', 'b1d2', 'g8f6', 'e2e4', 'd5e4', 'd3e4', 'f8c5',
                    'c2c3', 'e8g8', 'd1e2', 'f8e8',
                ],
            ],
            [
                'slug' => 'ecossaise',
                'name' => 'Partie ecossaise',
                'theme' => 'Ouverture directe du centre et activite tactique precoce.',
                'hint' => 'Le centre s ouvre vite : privilegie les coups qui developpent avec initiative.',
                'moves' => [
                    'e2e4', 'e7e5', 'g1f3', 'b8c6', 'd2d4', 'e5d4', 'f3d4', 'f8c5',
                    'd4c6', 'b7c6', 'f1d3', 'd7d5', 'e1g1', 'g8e7', 'c1f4', 'e8g8',
                    'b1d2', 'f8e8', 'd1h5', 'e7g6',
                ],
            ],
            [
                'slug' => 'pirc',
                'name' => 'Defense Pirc autrichienne',
                'theme' => 'Centre large pour les Blancs et contre-jeu noir a distance.',
                'hint' => 'Les Blancs occupent le centre puis developpent derriere leurs pions avances.',
                'moves' => [
                    'e2e4', 'd7d6', 'd2d4', 'g8f6', 'b1c3', 'g7g6', 'f2f4', 'f8g7',
                    'g1f3', 'e8g8', 'f1d3', 'b8c6', 'e1g1', 'c8g4', 'c1e3', 'e7e5',
                    'd4d5', 'c6d4', 'f3d4', 'e5d4',
                ],
            ],
        ];
    }

    private function determinerDifficulte(int $departPly, int $nombreCoupsBlancs): string
    {
        if ($nombreCoupsBlancs >= 4) {
            return 'extreme';
        }

        if ($nombreCoupsBlancs === 3) {
            return 'difficile';
        }

        return $departPly >= 8 ? 'medium' : 'facile';
    }

    /**
     * @return array<int, string>
     */
    private function construireIndices(string $hint, string $theme, int $nombreCoupsBlancs): array
    {
        $indices = [
            $hint,
            "Observe le centre, la coordination des pieces et la securite du roi. {$theme}",
            'La suite reste theorique : cherche les coups les plus naturels et economes en tempi.',
            'Plus la ligne avance, plus la precision compte : evite les coups spectaculaires inutiles.',
        ];

        return array_slice($indices, 0, $nombreCoupsBlancs);
    }

    private function genererFenApresPrefix(array $coupsPrefixe): string
    {
        $echiquier = $this->echiquierInitial();

        foreach ($coupsPrefixe as $coup) {
            $this->appliquerCoup($echiquier, $coup);
        }

        return $this->construireFenDepuisEchiquier($echiquier);
    }

    /**
     * @param array<string, string> $echiquier
     */
    private function appliquerCoup(array &$echiquier, string $coup): void
    {
        $caseDepart = substr($coup, 0, 2);
        $caseArrivee = substr($coup, 2, 2);
        $promotion = strlen($coup) > 4 ? substr($coup, 4, 1) : '';
        $piece = $echiquier[$caseDepart] ?? '';

        if ($piece === '') {
            throw new \RuntimeException(sprintf('Coup invalide dans le seeder dammier: %s', $coup));
        }

        if (strtolower($piece) === 'k' && $this->estRoque($caseDepart, $caseArrivee)) {
            $this->appliquerRoque($echiquier, $piece, $caseDepart, $caseArrivee);
        }

        $echiquier[$caseDepart] = '';
        $echiquier[$caseArrivee] = $promotion !== '' ? $this->piecePromue($piece, $promotion) : $piece;
    }

    private function estRoque(string $caseDepart, string $caseArrivee): bool
    {
        return in_array($caseDepart . $caseArrivee, ['e1g1', 'e1c1', 'e8g8', 'e8c8'], true);
    }

    /**
     * @param array<string, string> $echiquier
     */
    private function appliquerRoque(array &$echiquier, string $pieceRoi, string $caseDepart, string $caseArrivee): void
    {
        $mouvementsTour = match ($caseDepart . $caseArrivee) {
            'e1g1' => ['h1', 'f1'],
            'e1c1' => ['a1', 'd1'],
            'e8g8' => ['h8', 'f8'],
            'e8c8' => ['a8', 'd8'],
            default => null,
        };

        if ($mouvementsTour === null) {
            return;
        }

        [$departTour, $arriveeTour] = $mouvementsTour;
        $tour = $echiquier[$departTour] ?? ($pieceRoi === strtoupper($pieceRoi) ? 'R' : 'r');
        $echiquier[$departTour] = '';
        $echiquier[$arriveeTour] = $tour;
    }

    private function piecePromue(string $piece, string $promotion): string
    {
        return $piece === strtoupper($piece) ? strtoupper($promotion) : strtolower($promotion);
    }

    private function construireFenDepuisEchiquier(array $echiquier): string
    {
        $rangs = [];
        $fichiers = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

        for ($rang = 8; $rang >= 1; $rang -= 1) {
            $ligne = '';
            $casesVides = 0;

            foreach ($fichiers as $fichier) {
                $piece = $echiquier[$fichier . $rang] ?? '';

                if ($piece === '') {
                    $casesVides += 1;
                    continue;
                }

                if ($casesVides > 0) {
                    $ligne .= (string) $casesVides;
                    $casesVides = 0;
                }

                $ligne .= $piece;
            }

            if ($casesVides > 0) {
                $ligne .= (string) $casesVides;
            }

            $rangs[] = $ligne;
        }

        return implode('/', $rangs) . ' w - - 0 1';
    }

    /**
     * @return array<string, string>
     */
    private function echiquierInitial(): array
    {
        $echiquier = [];

        foreach (range('a', 'h') as $fichier) {
            $echiquier[$fichier . '2'] = 'P';
            $echiquier[$fichier . '7'] = 'p';
        }

        foreach ([
            'a1' => 'R', 'b1' => 'N', 'c1' => 'B', 'd1' => 'Q', 'e1' => 'K', 'f1' => 'B', 'g1' => 'N', 'h1' => 'R',
            'a8' => 'r', 'b8' => 'n', 'c8' => 'b', 'd8' => 'q', 'e8' => 'k', 'f8' => 'b', 'g8' => 'n', 'h8' => 'r',
        ] as $case => $piece) {
            $echiquier[$case] = $piece;
        }

        return $echiquier;
    }

    private function sequenceContientCoupNonSupporte(array $sequence): bool
    {
        foreach ($sequence as $coup) {
            if (strlen($coup) !== 4) {
                return true;
            }

            if (in_array($coup, ['e1g1', 'e1c1', 'e8g8', 'e8c8'], true)) {
                return true;
            }
        }

        return false;
    }
}
