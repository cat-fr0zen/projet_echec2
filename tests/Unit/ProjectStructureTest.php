<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ProjectStructureTest extends TestCase
{
    public function test_core_project_structure_is_present(): void
    {
        $root = dirname(__DIR__, 2);

        $expectedPaths = [
            'app/Http/Controllers/ActionController.php',
            'app/Http/Controllers/PageController.php',
            'database/migrations/2026_05_28_000001_create_reference_tables.php',
            'database/sql/create_database_mysql_mariadb.sql',
            'database/archives/oracle-19c-source/schema.sql',
            'lancement/demarrer_mysql_locale.bat',
            'lancement/installer_base_locale.bat',
            'runtime/mysql-data',
            'resources/views/pages/accueil.blade.php',
        ];

        foreach ($expectedPaths as $relativePath) {
            self::assertFileExists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        }
    }
}
