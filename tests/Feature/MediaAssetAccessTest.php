<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaPublication;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MediaAssetAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_media_publie_est_servi_depuis_la_route_protegee(): void
    {
        $auteur = $this->creerUtilisateur('media-public@example.test');
        $this->creerFichierMediaPrive('media_public_test.jpg', 'image');

        DB::table('media_publication')->insert([
            'identifiant' => 'media_public_test',
            'identifiant_auteur' => (string) $auteur['identifiant'],
            'code_type_media' => 'photo',
            'titre' => 'Photo publique',
            'description' => 'Photo de test',
            'nom_fichier_original' => 'photo.jpg',
            'nom_fichier_stocke' => 'media_public_test.jpg',
            'chemin_public' => 'assets/media/uploads/media_public_test.jpg',
            'type_mime' => 'image/jpeg',
            'taille_octets' => 5,
            'code_statut' => MediaPublication::STATUT_PUBLIE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        $this->get('/fichiers/medias/media_public_test.jpg')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $cacheControl = (string) $this->get('/fichiers/medias/media_public_test.jpg')->headers->get('Cache-Control');
        self::assertStringContainsString('public', $cacheControl);
        self::assertStringContainsString('max-age=300', $cacheControl);
        self::assertStringContainsString('stale-while-revalidate=60', $cacheControl);
    }

    public function test_un_media_non_publie_reste_inaccessible_au_visiteur(): void
    {
        $auteur = $this->creerUtilisateur('media-prive@example.test');
        $this->creerFichierMediaPrive('media_prive_test.jpg', 'image');

        DB::table('media_publication')->insert([
            'identifiant' => 'media_prive_test',
            'identifiant_auteur' => (string) $auteur['identifiant'],
            'code_type_media' => 'photo',
            'titre' => 'Photo privee',
            'description' => 'Photo en attente',
            'nom_fichier_original' => 'photo.jpg',
            'nom_fichier_stocke' => 'media_prive_test.jpg',
            'chemin_public' => 'assets/media/uploads/media_prive_test.jpg',
            'type_mime' => 'image/jpeg',
            'taille_octets' => 5,
            'code_statut' => MediaPublication::STATUT_EN_ATTENTE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        $this->get('/fichiers/medias/media_prive_test.jpg')
            ->assertNotFound();
    }

    public function test_un_media_non_publie_servi_a_son_auteur_reste_non_mis_en_cache_publiquement(): void
    {
        $auteur = $this->creerUtilisateur('media-auteur@example.test');
        $this->creerFichierMediaPrive('media_auteur_test.jpg', 'image');

        DB::table('media_publication')->insert([
            'identifiant' => 'media_auteur_test',
            'identifiant_auteur' => (string) $auteur['identifiant'],
            'code_type_media' => 'photo',
            'titre' => 'Photo auteur',
            'description' => 'Photo privee pour auteur',
            'nom_fichier_original' => 'photo.jpg',
            'nom_fichier_stocke' => 'media_auteur_test.jpg',
            'chemin_public' => 'assets/media/uploads/media_auteur_test.jpg',
            'type_mime' => 'image/jpeg',
            'taille_octets' => 5,
            'code_statut' => MediaPublication::STATUT_EN_ATTENTE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $auteur['identifiant'],
        ])->get('/fichiers/medias/media_auteur_test.jpg')
            ->assertOk();

        $cacheControl = (string) $this->withSession([
            'identifiant_utilisateur' => (string) $auteur['identifiant'],
        ])->get('/fichiers/medias/media_auteur_test.jpg')->headers->get('Cache-Control');
        self::assertStringContainsString('private', $cacheControl);
        self::assertStringContainsString('no-store', $cacheControl);
    }

    /**
     * @return array<string, mixed>
     */
    private function creerUtilisateur(string $courriel): array
    {
        return (new UserRepository)->creer([
            'nom' => 'Test',
            'prenom' => 'Media',
            'date_naissance' => '1990-01-02',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }

    private function creerFichierMediaPrive(string $nomFichier, string $contenu): void
    {
        $dossier = storage_path('app/private/uploads/medias');

        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        file_put_contents($dossier.DIRECTORY_SEPARATOR.$nomFichier, $contenu);
    }
}
