<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : TestCase.
 */

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath() . '/bootstrap/app.php';

        $storagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'projet_echec2-tests';

        foreach ([
            $storagePath,
            $storagePath . DIRECTORY_SEPARATOR . 'logs',
            $storagePath . DIRECTORY_SEPARATOR . 'framework',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions',
            $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
            $storagePath . DIRECTORY_SEPARATOR . 'app',
            $storagePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private',
            $storagePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . 'uploads',
        ] as $directory) {
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        $app->useStoragePath($storagePath);

        $app->make(Kernel::class)->bootstrap();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('logging.default', 'stderr');

        return $app;
    }
}
