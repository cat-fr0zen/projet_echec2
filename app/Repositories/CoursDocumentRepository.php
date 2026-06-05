<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class CoursDocumentRepository
{
    /**
     * @var array<string, string>
     */
    public const RUBRIQUES = [
        'livret_a' => 'Livret A',
        'livret_b' => 'Livret B',
        'livret_c' => 'Livret C',
        'livret_d' => 'Livret D',
        'livret_e' => 'Livret E',
        'cours' => 'Cours',
        'methodologie' => 'Methodologie',
        'strategie' => 'Strategie',
    ];

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function listerParRubrique(): array
    {
        $documents = array_map(
            fn (object $ligne): array => $this->normaliserDocument((array) $ligne),
            DB::table('document_cours')
                ->orderBy('code_rubrique')
                ->orderByDesc('cree_le')
                ->get()
                ->all()
        );

        $groupes = [];

        foreach (array_keys(self::RUBRIQUES) as $rubrique) {
            $groupes[$rubrique] = [];
        }

        foreach ($documents as $document) {
            $rubrique = (string) ($document['code_rubrique'] ?? '');

            if (! array_key_exists($rubrique, $groupes)) {
                $groupes[$rubrique] = [];
            }

            $groupes[$rubrique][] = $document;
        }

        return $groupes;
    }

    /**
     * @param  array<string, mixed>  $donnees
     * @return array<string, mixed>
     */
    public function creer(array $donnees): array
    {
        $identifiant = 'document_'.bin2hex(random_bytes(8));

        DB::table('document_cours')->insert([
            'identifiant_document' => $identifiant,
            'code_rubrique' => (string) ($donnees['code_rubrique'] ?? ''),
            'titre_document' => (string) ($donnees['titre_document'] ?? ''),
            'description_document' => $this->normaliserTexteOptionnel($donnees['description_document'] ?? null),
            'nom_fichier_original' => (string) ($donnees['nom_fichier_original'] ?? ''),
            'nom_fichier_stocke' => (string) ($donnees['nom_fichier_stocke'] ?? ''),
            'chemin_fichier' => (string) ($donnees['chemin_fichier'] ?? ''),
            'type_mime' => (string) ($donnees['type_mime'] ?? 'application/pdf'),
            'taille_octets' => (int) ($donnees['taille_octets'] ?? 0),
            'identifiant_auteur' => (string) ($donnees['identifiant_auteur'] ?? ''),
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trouverParIdentifiant(string $identifiantDocument): ?array
    {
        if (trim($identifiantDocument) === '') {
            return null;
        }

        $ligne = DB::table('document_cours')
            ->where('identifiant_document', trim($identifiantDocument))
            ->first();

        return $ligne !== null ? $this->normaliserDocument((array) $ligne) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trouverParNomFichierStocke(string $nomFichierStocke): ?array
    {
        if (trim($nomFichierStocke) === '') {
            return null;
        }

        $ligne = DB::table('document_cours')
            ->where('nom_fichier_stocke', trim($nomFichierStocke))
            ->first();

        return $ligne !== null ? $this->normaliserDocument((array) $ligne) : null;
    }

    public function supprimer(string $identifiantDocument): bool
    {
        if (trim($identifiantDocument) === '') {
            return false;
        }

        return DB::table('document_cours')
            ->where('identifiant_document', trim($identifiantDocument))
            ->delete() > 0;
    }

    public function rubriqueEstValide(string $rubrique): bool
    {
        return array_key_exists($rubrique, self::RUBRIQUES);
    }

    /**
     * @return array<string, mixed>
     */
    private function normaliserDocument(array $ligne): array
    {
        return [
            'identifiant_document' => (string) ($ligne['identifiant_document'] ?? ''),
            'code_rubrique' => (string) ($ligne['code_rubrique'] ?? ''),
            'titre_document' => (string) ($ligne['titre_document'] ?? ''),
            'description_document' => (string) ($ligne['description_document'] ?? ''),
            'nom_fichier_original' => (string) ($ligne['nom_fichier_original'] ?? ''),
            'nom_fichier_stocke' => (string) ($ligne['nom_fichier_stocke'] ?? ''),
            'chemin_fichier' => (string) ($ligne['chemin_fichier'] ?? ''),
            'type_mime' => (string) ($ligne['type_mime'] ?? 'application/pdf'),
            'taille_octets' => (int) ($ligne['taille_octets'] ?? 0),
            'identifiant_auteur' => (string) ($ligne['identifiant_auteur'] ?? ''),
            'cree_le' => (string) ($ligne['cree_le'] ?? ''),
            'mis_a_jour_le' => (string) ($ligne['mis_a_jour_le'] ?? ''),
        ];
    }

    private function normaliserTexteOptionnel(mixed $valeur): ?string
    {
        $texte = trim((string) $valeur);

        return $texte !== '' ? $texte : null;
    }
}
