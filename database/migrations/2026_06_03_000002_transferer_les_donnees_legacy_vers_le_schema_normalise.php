<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ref_statut_newsletter_abonnement')->insertOrIgnore([
            ['code_statut' => 'actif', 'libelle_statut' => 'Actif'],
            ['code_statut' => 'desabonne', 'libelle_statut' => 'Desabonne'],
        ]);

        DB::table('ref_type_evenement_newsletter')->insertOrIgnore([
            ['code_type_evenement' => 'article', 'libelle_type_evenement' => 'Article'],
            ['code_type_evenement' => 'cours', 'libelle_type_evenement' => 'Cours'],
            ['code_type_evenement' => 'boutique', 'libelle_type_evenement' => 'Boutique'],
            ['code_type_evenement' => 'confirmation', 'libelle_type_evenement' => 'Confirmation'],
        ]);

        DB::table('ref_statut_envoi_newsletter')->insertOrIgnore([
            ['code_statut_envoi' => 'envoye', 'libelle_statut_envoi' => 'Envoye'],
            ['code_statut_envoi' => 'echec', 'libelle_statut_envoi' => 'Echec'],
            ['code_statut_envoi' => 'ignore', 'libelle_statut_envoi' => 'Ignore'],
        ]);

        foreach (DB::table('compte_membre')->select(['identifiant', 'date_naissance'])->get() as $compte) {
            DB::table('compte_membre')
                ->where('identifiant', $compte->identifiant)
                ->update([
                    'date_naissance_normalisee' => $this->normaliserDate((string) ($compte->date_naissance ?? '')),
                ]);
        }

        DB::table('article')->update([
            'contenu_plat_cache' => DB::raw('contenu'),
        ]);

        foreach (DB::table('newsletter_abonnement')->select(['identifiant_abonnement', 'statut'])->get() as $abonnement) {
            DB::table('newsletter_abonnement')
                ->where('identifiant_abonnement', $abonnement->identifiant_abonnement)
                ->update([
                    'code_statut' => $this->normaliserStatutAbonnement((string) ($abonnement->statut ?? '')),
                ]);
        }

        foreach (DB::table('newsletter_envoi')->select(['identifiant_envoi', 'type_evenement', 'statut_envoi'])->get() as $envoi) {
            DB::table('newsletter_envoi')
                ->where('identifiant_envoi', $envoi->identifiant_envoi)
                ->update([
                    'code_type_evenement' => $this->normaliserTypeEvenement((string) ($envoi->type_evenement ?? '')),
                    'code_statut_envoi' => $this->normaliserStatutEnvoi((string) ($envoi->statut_envoi ?? '')),
                ]);
        }

        foreach (DB::table('horaire_creneau')->select(['identifiant_creneau', 'horaire'])->get() as $creneau) {
            [$heureDebut, $heureFin] = $this->decomposerPlageHoraire((string) ($creneau->horaire ?? ''));

            DB::table('horaire_creneau')
                ->where('identifiant_creneau', $creneau->identifiant_creneau)
                ->update([
                    'heure_debut' => $heureDebut,
                    'heure_fin' => $heureFin,
                ]);
        }

        foreach (DB::table('dammier_puzzle')->select(['dammier_id', 'solution', 'reponses', 'indices'])->get() as $puzzle) {
            $puzzleId = (string) $puzzle->dammier_id;

            foreach ($this->texteVersListe((string) ($puzzle->solution ?? '')) as $index => $coup) {
                DB::table('dammier_solution_etape')->insert([
                    'identifiant_etape' => sprintf('solution_%s_%02d', $puzzleId, $index + 1),
                    'dammier_puzzle_id' => $puzzleId,
                    'ordre_etape' => $index + 1,
                    'coup' => $coup,
                ]);
            }

            foreach ($this->texteVersListe((string) ($puzzle->reponses ?? '')) as $index => $coup) {
                DB::table('dammier_reponse_attendue')->insert([
                    'identifiant_reponse' => sprintf('reponse_%s_%02d', $puzzleId, $index + 1),
                    'dammier_puzzle_id' => $puzzleId,
                    'ordre_reponse' => $index + 1,
                    'coup' => $coup,
                ]);
            }

            foreach ($this->texteVersListe((string) ($puzzle->indices ?? '')) as $index => $indice) {
                DB::table('dammier_indice')->insert([
                    'identifiant_indice' => sprintf('indice_%s_%02d', $puzzleId, $index + 1),
                    'dammier_puzzle_id' => $puzzleId,
                    'ordre_indice' => $index + 1,
                    'texte_indice' => $indice,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('dammier_indice')->delete();
        DB::table('dammier_reponse_attendue')->delete();
        DB::table('dammier_solution_etape')->delete();

        DB::table('newsletter_envoi')->update([
            'code_type_evenement' => null,
            'code_statut_envoi' => null,
        ]);

        DB::table('newsletter_abonnement')->update([
            'code_statut' => null,
        ]);

        DB::table('horaire_creneau')->update([
            'heure_debut' => null,
            'heure_fin' => null,
        ]);

        DB::table('article')->update([
            'contenu_plat_cache' => null,
        ]);

        DB::table('compte_membre')->update([
            'date_naissance_normalisee' => null,
        ]);
    }

    private function normaliserDate(string $date): ?string
    {
        $date = trim($date);

        if ($date === '') {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y'];

        foreach ($formats as $format) {
            $objet = \DateTimeImmutable::createFromFormat($format, $date);

            if ($objet instanceof \DateTimeImmutable) {
                return $objet->format('Y-m-d');
            }
        }

        return null;
    }

    private function normaliserStatutAbonnement(string $statut): string
    {
        return trim($statut) === 'desabonne' ? 'desabonne' : 'actif';
    }

    private function normaliserTypeEvenement(string $type): string
    {
        $type = trim($type);
        $typesAutorises = ['article', 'cours', 'boutique', 'confirmation'];

        return in_array($type, $typesAutorises, true) ? $type : 'confirmation';
    }

    private function normaliserStatutEnvoi(string $statut): string
    {
        $statut = trim($statut);
        $statutsAutorises = ['envoye', 'echec', 'ignore'];

        return in_array($statut, $statutsAutorises, true) ? $statut : 'ignore';
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

    /**
     * @return array<int, string>
     */
    private function texteVersListe(string $valeur): array
    {
        if (trim($valeur) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $valeur) ?: []),
            static fn (string $item): bool => $item !== ''
        ));
    }
};
