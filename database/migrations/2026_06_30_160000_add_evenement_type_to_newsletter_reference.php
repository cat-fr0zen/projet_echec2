<?php
/**
 * Ajoute le type newsletter "evenement".
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ref_type_evenement_newsletter')->insertOrIgnore([
            'code_type_evenement' => 'evenement',
            'libelle_type_evenement' => 'Evenement',
        ]);
    }

    public function down(): void
    {
        DB::table('ref_type_evenement_newsletter')
            ->where('code_type_evenement', 'evenement')
            ->delete();
    }
};
