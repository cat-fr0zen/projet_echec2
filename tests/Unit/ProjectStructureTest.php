<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ProjectStructureTest.
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ProjectStructureTest extends TestCase
{
    public function test_core_project_structure_is_present(): void
    {
        $root = dirname(__DIR__, 2);

        $expectedPaths = [
            'app/Http/Controllers/ActionController.php',
            'app/Http/Controllers/CoursDocumentController.php',
            'app/Http/Controllers/PageController.php',
            'app/Support/SiteActionHandler.php',
            'app/Support/SitePageRenderer.php',
            'app/Support/SiteContent.php',
            'database/migrations/2026_05_28_000001_create_reference_tables.php',
            'database/migrations/2026_06_03_000001_etendre_schema_pour_normalisation_bcnf.php',
            'database/sql/create_database_mysql_mariadb.sql',
            'README.md',
            'lancement/demarrer_tout_le_site.bat',
            'runtime/mysql-data',
            'resources/views/pages/cours.blade.php',
            'resources/views/pages/cours_livrets.blade.php',
            'resources/views/pages/cours_livret.blade.php',
            'resources/views/pages/cours_progression.blade.php',
            'resources/views/pages/cours_rubrique.blade.php',
            'resources/views/pages/accueil.blade.php',
        ];

        foreach ($expectedPaths as $relativePath) {
            self::assertFileExists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        }
    }

    public function test_project_keeps_only_one_markdown_file_and_one_bat_file(): void
    {
        $root = dirname(__DIR__, 2);

        $markdownFiles = [];
        $batFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $relativePath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if ($extension === 'md') {
                $markdownFiles[] = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            }

            if ($extension === 'bat') {
                $batFiles[] = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            }
        }

        self::assertSame(['README.md'], $markdownFiles);
        self::assertSame(['lancement/demarrer_tout_le_site.bat'], $batFiles);
    }
}
