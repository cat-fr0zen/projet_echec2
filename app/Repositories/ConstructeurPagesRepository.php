<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ConstructeurPagesRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Throwable;

final class ConstructeurPagesRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerPourPage(string $codePage): array
    {
        try {
            $blocs = DB::table('constructeur_page_bloc')
                ->where('code_page', $codePage)
                ->orderBy('ordre_affichage')
                ->get()
                ->map(fn (object $bloc): array => $this->normaliserBloc((array) $bloc))
                ->all();

            if ($blocs !== []) {
                return $blocs;
            }
        } catch (Throwable) {
        }

        return array_map(
            fn (array $bloc): array => $this->normaliserBloc($bloc),
            self::definitionsParDefaut($codePage)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerActifsPourPage(string $codePage): array
    {
        return array_values(
            array_filter(
                $this->listerPourPage($codePage),
                static fn (array $bloc): bool => (bool) ($bloc['est_actif'] ?? false)
            )
        );
    }

    /**
     * @param array<string, array<string, mixed>> $blocs
     */
    public function mettreAJourBlocsAccueil(array $blocs): void
    {
        $blocsExistants = $this->listerPourPage('accueil');

        if ($blocsExistants === []) {
            return;
        }

        $blocsVerrouilles = [];
        $blocsMobiles = [];

        foreach ($blocsExistants as $blocExistant) {
            $codeBloc = (string) ($blocExistant['code_bloc'] ?? '');
            $miseAJour = $blocs[$codeBloc] ?? [];

            if ((bool) ($blocExistant['est_verrouille'] ?? false)) {
                $blocExistant = $this->fusionnerContenuPersonnalise($blocExistant, $miseAJour);
                $blocsVerrouilles[] = $blocExistant;
                continue;
            }

            $blocExistant['ordre_souhaite'] = max(1, (int) ($miseAJour['ordre_affichage'] ?? $blocExistant['ordre_affichage'] ?? 1));
            $blocExistant['est_actif'] = (bool) ($miseAJour['est_actif'] ?? false);
            $blocExistant = $this->fusionnerContenuPersonnalise($blocExistant, $miseAJour);
            $blocsMobiles[] = $blocExistant;
        }

        usort(
            $blocsMobiles,
            static function (array $blocA, array $blocB): int {
                $ordreCompare = ((int) ($blocA['ordre_souhaite'] ?? 1)) <=> ((int) ($blocB['ordre_souhaite'] ?? 1));

                if ($ordreCompare !== 0) {
                    return $ordreCompare;
                }

                return ((int) ($blocA['ordre_affichage'] ?? 1)) <=> ((int) ($blocB['ordre_affichage'] ?? 1));
            }
        );

        $ordreCourant = count($blocsVerrouilles) + 1;
        $instant = now();

        foreach ($blocsMobiles as $blocMobile) {
            DB::table('constructeur_page_bloc')
                ->where('code_page', 'accueil')
                ->where('code_bloc', (string) $blocMobile['code_bloc'])
                ->update([
                    'ordre_affichage' => $ordreCourant,
                    'est_actif' => (bool) ($blocMobile['est_actif'] ?? false),
                    'titre_personnalise' => (string) ($blocMobile['titre_personnalise'] ?? ''),
                    'contenu_personnalise' => (string) ($blocMobile['contenu_personnalise'] ?? ''),
                    'updated_at' => $instant,
                ]);

            $ordreCourant++;
        }

        foreach ($blocsVerrouilles as $blocVerrouille) {
            DB::table('constructeur_page_bloc')
                ->where('code_page', 'accueil')
                ->where('code_bloc', (string) $blocVerrouille['code_bloc'])
                ->update([
                    'titre_personnalise' => (string) ($blocVerrouille['titre_personnalise'] ?? ''),
                    'contenu_personnalise' => (string) ($blocVerrouille['contenu_personnalise'] ?? ''),
                    'updated_at' => $instant,
                ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitionsParDefaut(string $codePage): array
    {
        if ($codePage !== 'accueil') {
            return [];
        }

        $instant = now();

        return [
            [
                'code_page' => 'accueil',
                'code_bloc' => 'bandeau_accueil',
                'libelle_bloc' => "Bandeau d'accueil",
                'description_bloc' => 'Le grand encart de bienvenue avec les boutons principaux.',
                'ordre_affichage' => 1,
                'est_actif' => true,
                'est_verrouille' => true,
                'titre_personnalise' => '',
                'contenu_personnalise' => '',
                'created_at' => $instant,
                'updated_at' => $instant,
            ],
            [
                'code_page' => 'accueil',
                'code_bloc' => 'casse_tete_hebdomadaire',
                'libelle_bloc' => 'Casse-tête hebdomadaire',
                'description_bloc' => "Le puzzle d'échecs de la semaine.",
                'ordre_affichage' => 2,
                'est_actif' => true,
                'est_verrouille' => true,
                'titre_personnalise' => '',
                'contenu_personnalise' => '',
                'created_at' => $instant,
                'updated_at' => $instant,
            ],
            [
                'code_page' => 'accueil',
                'code_bloc' => 'mot_du_club',
                'libelle_bloc' => 'Mot du club',
                'description_bloc' => 'Le texte de présentation du club.',
                'ordre_affichage' => 3,
                'est_actif' => true,
                'est_verrouille' => false,
                'titre_personnalise' => 'Présentation',
                'contenu_personnalise' => "Bienvenue chez Les Cavaliers d'Hérouville, un club d'échecs pas comme les autres ! Notre mission ? Faire découvrir et partager la passion du jeu d'échecs à tous. Des 5 ans jusqu'à 105 ans. Débutants curieux ou pros de la stratégie. Convivialité, apprentissage, progression... le tout dans la bonne humeur ! Que vous vouliez apprendre, progresser ou simplement jouer pour le plaisir... Venez faire travailler vos neurones dans une ambiance chaleureuse et stimulante ! Rejoignez-nous et faites partie d'une communauté passionnée !",
                'created_at' => $instant,
                'updated_at' => $instant,
            ],
            [
                'code_page' => 'accueil',
                'code_bloc' => 'liens_utiles',
                'libelle_bloc' => 'Liens utiles',
                'description_bloc' => 'Les liens rapides vers la fédération et la ligue.',
                'ordre_affichage' => 4,
                'est_actif' => true,
                'est_verrouille' => false,
                'titre_personnalise' => '',
                'contenu_personnalise' => '',
                'created_at' => $instant,
                'updated_at' => $instant,
            ],
            [
                'code_page' => 'accueil',
                'code_bloc' => 'pieces_echecs',
                'libelle_bloc' => "Pièces d'échecs",
                'description_bloc' => 'Le carrousel qui explique les pièces et leurs mouvements.',
                'ordre_affichage' => 5,
                'est_actif' => true,
                'est_verrouille' => false,
                'titre_personnalise' => '',
                'contenu_personnalise' => '',
                'created_at' => $instant,
                'updated_at' => $instant,
            ],
            [
                'code_page' => 'accueil',
                'code_bloc' => 'chiffres_du_club',
                'libelle_bloc' => 'Chiffres du club',
                'description_bloc' => 'Les cartes résumées et le détail complet des horaires.',
                'ordre_affichage' => 6,
                'est_actif' => true,
                'est_verrouille' => false,
                'titre_personnalise' => '',
                'contenu_personnalise' => '',
                'created_at' => $instant,
                'updated_at' => $instant,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $bloc
     * @return array<string, mixed>
     */
    private function normaliserBloc(array $bloc): array
    {
        return [
            'code_page' => (string) ($bloc['code_page'] ?? ''),
            'code_bloc' => (string) ($bloc['code_bloc'] ?? ''),
            'libelle_bloc' => (string) ($bloc['libelle_bloc'] ?? ''),
            'description_bloc' => (string) ($bloc['description_bloc'] ?? ''),
            'ordre_affichage' => (int) ($bloc['ordre_affichage'] ?? 0),
            'est_actif' => (bool) ($bloc['est_actif'] ?? false),
            'est_verrouille' => (bool) ($bloc['est_verrouille'] ?? false),
            'titre_personnalise' => (string) ($bloc['titre_personnalise'] ?? ''),
            'contenu_personnalise' => (string) ($bloc['contenu_personnalise'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $bloc
     * @param array<string, mixed> $miseAJour
     * @return array<string, mixed>
     */
    private function fusionnerContenuPersonnalise(array $bloc, array $miseAJour): array
    {
        if (array_key_exists('titre_personnalise', $miseAJour)) {
            $bloc['titre_personnalise'] = trim((string) $miseAJour['titre_personnalise']);
        }

        if (array_key_exists('contenu_personnalise', $miseAJour)) {
            $bloc['contenu_personnalise'] = trim((string) $miseAJour['contenu_personnalise']);
        }

        return $bloc;
    }
}
