<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ArticleAssetAccessTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ArticleAssetAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_media_d_article_publie_est_servi_depuis_la_route_protegee(): void
    {
        $auteur = $this->creerUtilisateur('article-public@example.test');
        $this->creerFichierArticlePrive('article_public_test.jpg', 'image');

        DB::table('article')->insert([
            'identifiant' => 'article_public_test',
            'identifiant_auteur' => (string) $auteur['identifiant'],
            'titre' => 'Article public',
            'resume' => 'Resume public',
            'contenu_plat_cache' => 'Contenu de test suffisamment long pour passer.',
            'code_statut' => Article::STATUT_PUBLIE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        DB::table('article_bloc')->insert([
            'identifiant_bloc' => 'article_bloc_public_test',
            'identifiant_article' => 'article_public_test',
            'ordre_affichage' => 1,
            'code_type' => Article::TYPE_BLOC_IMAGE,
            'texte' => null,
            'chemin_public' => 'assets/media/uploads/articles/article_public_test.jpg',
            'type_mime' => 'image/jpeg',
            'texte_alternatif' => 'Image de test',
            'legende' => 'Legende',
            'nom_fichier_original' => 'photo.jpg',
            'taille_octets' => 5,
        ]);

        $this->get('/fichiers/articles/article_public_test.jpg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_un_media_d_article_en_attente_reste_inaccessible_au_visiteur(): void
    {
        $auteur = $this->creerUtilisateur('article-prive@example.test');
        $this->creerFichierArticlePrive('article_prive_test.jpg', 'image');

        DB::table('article')->insert([
            'identifiant' => 'article_prive_test',
            'identifiant_auteur' => (string) $auteur['identifiant'],
            'titre' => 'Article prive',
            'resume' => 'Resume prive',
            'contenu_plat_cache' => 'Contenu de test suffisamment long pour passer.',
            'code_statut' => Article::STATUT_EN_ATTENTE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        DB::table('article_bloc')->insert([
            'identifiant_bloc' => 'article_bloc_prive_test',
            'identifiant_article' => 'article_prive_test',
            'ordre_affichage' => 1,
            'code_type' => Article::TYPE_BLOC_IMAGE,
            'texte' => null,
            'chemin_public' => 'assets/media/uploads/articles/article_prive_test.jpg',
            'type_mime' => 'image/jpeg',
            'texte_alternatif' => 'Image de test',
            'legende' => 'Legende',
            'nom_fichier_original' => 'photo.jpg',
            'taille_octets' => 5,
        ]);

        $this->get('/fichiers/articles/article_prive_test.jpg')
            ->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function creerUtilisateur(string $courriel): array
    {
        return (new UserRepository)->creer([
            'nom' => 'Test',
            'prenom' => 'Article',
            'date_naissance' => '1990-01-02',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }

    private function creerFichierArticlePrive(string $nomFichier, string $contenu): void
    {
        $dossier = storage_path('app/private/uploads/articles');

        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        file_put_contents($dossier.DIRECTORY_SEPARATOR.$nomFichier, $contenu);
    }
}
