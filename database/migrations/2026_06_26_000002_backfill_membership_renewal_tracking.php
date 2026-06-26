<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : backfill membership renewal tracking.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $maintenant = new DateTimeImmutable('now');
        $saisonCourante = $this->calculerSaisonPourDate($maintenant);

        DB::table('compte_membre')
            ->where('code_statut_adhesion', 'active')
            ->whereNull('saison_adhesion_active')
            ->update([
                'saison_adhesion_active' => $saisonCourante,
                'adhesion_renouvelee_le' => $maintenant->format('Y-m-d H:i:s'),
            ]);
    }

    public function down(): void
    {
        DB::table('compte_membre')->update([
            'saison_adhesion_active' => null,
            'saison_relance_adhesion' => null,
            'adhesion_renouvelee_le' => null,
        ]);
    }

    private function calculerSaisonPourDate(DateTimeImmutable $date): string
    {
        $annee = (int) $date->format('Y');
        $mois = (int) $date->format('n');
        $debut = $mois >= 9 ? $annee : $annee - 1;

        return sprintf('%d-%d', $debut, $debut + 1);
    }
};
