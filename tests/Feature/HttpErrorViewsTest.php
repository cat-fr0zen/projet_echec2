<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class HttpErrorViewsTest extends TestCase
{
    public function test_les_20_vues_derreur_http_les_plus_courantes_sont_presentes(): void
    {
        $codes = [400, 401, 402, 403, 404, 405, 408, 409, 410, 413, 414, 419, 422, 423, 429, 500, 501, 502, 503, 504];

        foreach ($codes as $code) {
            $this->assertFileExists(resource_path('views/errors/'.$code.'.blade.php'));
        }
    }

    public function test_une_vue_derreur_500_peut_etre_compilee_et_affiche_le_message_personnalise(): void
    {
        $html = Blade::render("@include('errors.500')");

        $this->assertStringContainsString('Erreur 500', $html);
        $this->assertStringContainsString("Cavaliers d'Hérouville", $html);
        $this->assertStringContainsString('Le roi est tombe sur une erreur serveur.', $html);
    }

    public function test_la_404_personnalisee_reste_visible_sur_url_inconnue(): void
    {
        $this->get('/url-inconnue-echecs')
            ->assertNotFound()
            ->assertSee('Vous êtes en échec &amp; mat.', false);
    }
}
