<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SchemaMigrationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('schema_migration')->updateOrInsert(
            ['version_schema' => 'mysql.1.0'],
            [
                'nom_migration' => 'schema_normalise_mysql_laravel',
                'categorie' => 'normalisation',
                'checksum' => null,
                'commentaire' => 'Schema relationnel normalise vers une forme proche Boyce-Codd pour MySQL/MariaDB.',
                'applique_le' => now(),
            ]
        );
    }
}
