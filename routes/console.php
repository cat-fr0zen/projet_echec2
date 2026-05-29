<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about-local', function (): void {
    $this->info('Port Laravel local du site Cavaliers d\'Herouville.');
})->purpose('Affiche un resume rapide du port Laravel local');
