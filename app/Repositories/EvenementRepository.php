<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : EvenementRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

final class EvenementRepository
{
    private const CLE_PARAMETRE = 'site_evenements_speciaux';

    public function __construct(
        private ?ParametreSiteRepository $parametreSiteRepository = null
    ) {
        $this->parametreSiteRepository ??= new ParametreSiteRepository;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function lister(): array
    {
        $json = $this->parametreSiteRepository->obtenirTexte(self::CLE_PARAMETRE, '[]');
        $donnees = json_decode($json, true);

        if (! is_array($donnees)) {
            return [];
        }

        $evenements = array_values(array_filter(array_map(
            fn (mixed $evenement): ?array => is_array($evenement) ? $this->normaliserEvenement($evenement) : null,
            $donnees
        )));

        usort(
            $evenements,
            static fn (array $a, array $b): int => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''))
        );

        return $evenements;
    }

    /**
     * @param array<string, mixed> $donnees
     * @return array<string, string>
     */
    public function ajouter(array $donnees): array
    {
        $evenements = $this->lister();
        $evenement = $this->normaliserEvenement([
            'identifiant' => 'evenement_' . bin2hex(random_bytes(8)),
            ...$donnees,
        ]);

        $evenements[] = $evenement;
        $this->sauvegarder($evenements);

        return $evenement;
    }

    public function supprimer(string $identifiant): bool
    {
        $identifiant = trim($identifiant);

        if ($identifiant === '') {
            return false;
        }

        $evenements = $this->lister();
        $avant = count($evenements);
        $evenements = array_values(array_filter(
            $evenements,
            static fn (array $evenement): bool => (string) ($evenement['identifiant'] ?? '') !== $identifiant
        ));

        if ($avant === count($evenements)) {
            return false;
        }

        $this->sauvegarder($evenements);

        return true;
    }

    /**
     * @param array<int, array<string, string>> $evenements
     */
    private function sauvegarder(array $evenements): void
    {
        $this->parametreSiteRepository->mettreAJourTexte(
            self::CLE_PARAMETRE,
            json_encode(array_values($evenements), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]'
        );
    }

    /**
     * @param array<string, mixed> $evenement
     * @return array<string, string>
     */
    private function normaliserEvenement(array $evenement): array
    {
        return [
            'identifiant' => trim((string) ($evenement['identifiant'] ?? '')),
            'titre' => trim((string) ($evenement['titre'] ?? '')),
            'date' => trim((string) ($evenement['date'] ?? '')),
            'lieu' => trim((string) ($evenement['lieu'] ?? '')),
            'description' => trim((string) ($evenement['description'] ?? '')),
        ];
    }
}
