<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DatabaseSeeder.
 */

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceTablesSeeder::class,
            ClubScheduleSeeder::class,
            DammierPuzzleSeeder::class,
            ConstructeurAccueilSeeder::class,
            SchemaMigrationSeeder::class,
        ]);
    }
}
