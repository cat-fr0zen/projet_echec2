<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : GitIgnoreRulesTest.
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class GitIgnoreRulesTest extends TestCase
{
    public function test_local_sensitive_and_cache_files_are_ignored(): void
    {
        $racine = dirname(__DIR__, 2);
        $contenu = file_get_contents($racine . DIRECTORY_SEPARATOR . '.gitignore');

        self::assertIsString($contenu);

        $motifsAttendus = [
            '/.env',
            '/.phpunit.result.cache',
            '/storage/framework/cache/*',
            '/storage/framework/sessions/*',
            '/storage/framework/views/*',
            '/storage/logs/*.log',
            '/runtime/mysql-data/*',
            '/runtime/mysql-local.ini',
            '/runtime/mysql-data-archive-*/',
            '/lancement/*',
            '!/lancement/demarrer_tout_le_site.bat',
        ];

        foreach ($motifsAttendus as $motif) {
            self::assertStringContainsString($motif, $contenu);
        }
    }
}
