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
            ['version_schema' => 'mysql.0.0'],
            [
                'nom_migration' => 'schema_initial_mysql_laravel',
                'categorie' => 'foundation',
                'checksum' => null,
                'commentaire' => 'Schema relationnel initial cible MySQL/MariaDB via migrations Laravel.',
                'applique_le' => now(),
            ]
        );
    }
}
