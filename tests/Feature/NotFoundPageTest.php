<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class NotFoundPageTest extends TestCase
{
    public function test_une_url_inconnue_affiche_la_page_404_personnalisee(): void
    {
        $this->get('/page-inconnue-test')
            ->assertNotFound()
            ->assertSee('Vous êtes en échec &amp; mat.', false)
            ->assertSee('Erreur 404', false)
            ->assertSee('Revenir à l\'accueil', false);
    }

    public function test_un_article_introuvable_reste_en_vraie_404(): void
    {
        $this->get('/articles/article-inexistant')
            ->assertNotFound()
            ->assertSee('Vous êtes en échec &amp; mat.', false);
    }
}
