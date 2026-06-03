<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ArticleRepository;
use App\Repositories\DammierRepository;
use App\Repositories\MediaRepository;
use App\Repositories\NewsletterRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ScheduleRepository;
use Database\Seeders\ClubScheduleSeeder;
use Database\Seeders\ReferenceTablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SchemaNormalisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_schema_normalise_remplace_les_champs_legacy(): void
    {
        $this->seed([ReferenceTablesSeeder::class, ClubScheduleSeeder::class]);

        self::assertTrue(Schema::hasTable('dammier_solution_etape'));
        self::assertTrue(Schema::hasTable('dammier_reponse_attendue'));
        self::assertTrue(Schema::hasTable('dammier_indice'));
        self::assertTrue(Schema::hasTable('ref_statut_newsletter_abonnement'));
        self::assertTrue(Schema::hasTable('ref_type_evenement_newsletter'));
        self::assertTrue(Schema::hasTable('ref_statut_envoi_newsletter'));

        self::assertTrue(Schema::hasColumn('article', 'contenu_plat_cache'));
        self::assertFalse(Schema::hasColumn('article', 'nom_auteur'));
        self::assertFalse(Schema::hasColumn('article', 'auteur_affiche'));
        self::assertFalse(Schema::hasColumn('article', 'contenu'));

        self::assertTrue(Schema::hasColumn('compte_membre', 'date_naissance'));
        self::assertFalse($this->colonneEstTexteLegacyPourDateNaissance());

        self::assertFalse(Schema::hasColumn('media_publication', 'nom_auteur'));
        self::assertFalse(Schema::hasColumn('commande_locale', 'nom_utilisateur'));
        self::assertFalse(Schema::hasColumn('dammier_score', 'dammier_display_name'));
        self::assertFalse(Schema::hasColumn('dammier_puzzle', 'solution'));
        self::assertFalse(Schema::hasColumn('dammier_puzzle', 'reponses'));
        self::assertFalse(Schema::hasColumn('dammier_puzzle', 'indices'));

        self::assertTrue(Schema::hasColumn('horaire_creneau', 'heure_debut'));
        self::assertTrue(Schema::hasColumn('horaire_creneau', 'heure_fin'));
        self::assertFalse(Schema::hasColumn('horaire_creneau', 'horaire'));

        self::assertTrue(Schema::hasColumn('newsletter_abonnement', 'code_statut'));
        self::assertFalse(Schema::hasColumn('newsletter_abonnement', 'statut'));
        self::assertTrue(Schema::hasColumn('newsletter_envoi', 'code_type_evenement'));
        self::assertTrue(Schema::hasColumn('newsletter_envoi', 'code_statut_envoi'));
        self::assertFalse(Schema::hasColumn('newsletter_envoi', 'type_evenement'));
        self::assertFalse(Schema::hasColumn('newsletter_envoi', 'statut_envoi'));
    }

    public function test_les_depots_conservent_les_cles_utilisees_par_les_vues(): void
    {
        $this->seed([ReferenceTablesSeeder::class, ClubScheduleSeeder::class]);

        DB::table('ref_statut_newsletter_abonnement')->insert([
            'code_statut' => 'actif',
            'libelle_statut' => 'Actif',
        ]);
        DB::table('ref_type_evenement_newsletter')->insert([
            'code_type_evenement' => 'article',
            'libelle_type_evenement' => 'Article',
        ]);
        DB::table('ref_statut_envoi_newsletter')->insert([
            'code_statut_envoi' => 'envoye',
            'libelle_statut_envoi' => 'Envoye',
        ]);

        DB::table('compte_membre')->insert([
            'identifiant' => 'utilisateur_test',
            'nom' => 'Dupont',
            'prenom' => 'Jeanne',
            'date_naissance' => '2000-01-02',
            'courriel' => 'jeanne.dupont@example.test',
            'courriel_normalise' => 'jeanne.dupont@example.test',
            'numero_licence_federale' => 'ABC123',
            'mot_de_passe_hache' => password_hash('secret', PASSWORD_DEFAULT),
            'description_profil' => 'Membre test',
            'pseudo_chess' => 'jeannechess',
            'code_role' => 'admin',
            'code_statut_compte' => 'actif',
            'code_statut_adhesion' => 'active',
            'cree_le' => '2026-06-03 10:00:00',
        ]);

        DB::table('article')->insert([
            'identifiant' => 'article_test',
            'identifiant_auteur' => 'utilisateur_test',
            'titre' => 'Article test',
            'resume' => 'Resume test',
            'contenu_plat_cache' => 'Contenu plat',
            'code_statut' => 'publie',
            'cree_le' => '2026-06-03 11:00:00',
        ]);

        DB::table('article_bloc')->insert([
            'identifiant_bloc' => 'bloc_test',
            'identifiant_article' => 'article_test',
            'ordre_affichage' => 1,
            'code_type' => 'paragraphe',
            'texte' => 'Paragraphe article',
            'taille_octets' => 0,
        ]);

        DB::table('media_publication')->insert([
            'identifiant' => 'media_test',
            'identifiant_auteur' => 'utilisateur_test',
            'code_type_media' => 'photo',
            'titre' => 'Photo test',
            'description' => 'Description photo',
            'nom_fichier_original' => 'photo.jpg',
            'nom_fichier_stocke' => 'photo-stockee.jpg',
            'chemin_public' => '/uploads/photo-stockee.jpg',
            'type_mime' => 'image/jpeg',
            'taille_octets' => 1234,
            'code_statut' => 'publie',
            'cree_le' => '2026-06-03 12:00:00',
        ]);

        DB::table('commande_locale')->insert([
            'identifiant' => 'commande_test',
            'identifiant_utilisateur' => 'utilisateur_test',
            'produit' => 'T-shirt club',
            'categorie' => 'textile',
            'code_statut' => 'en_attente',
            'cree_le' => '2026-06-03 13:00:00',
        ]);

        DB::table('dammier_puzzle')->insert([
            'dammier_id' => 'puzzle_test',
            'titre' => 'Puzzle test',
            'description' => 'Description puzzle',
            'instruction' => 'Trouver le mat en deux.',
            'fen' => '8/8/8/8/8/8/8/8 w - - 0 1',
            'trait' => 'w',
            'source_puzzle' => 'pool_local',
            'actif' => true,
            'cree_le' => '2026-06-03 14:00:00',
        ]);

        DB::table('dammier_solution_etape')->insert([
            'identifiant_etape' => 'solution_test_1',
            'dammier_puzzle_id' => 'puzzle_test',
            'ordre_etape' => 1,
            'coup' => 'Qh7#',
        ]);

        DB::table('dammier_reponse_attendue')->insert([
            'identifiant_reponse' => 'reponse_test_1',
            'dammier_puzzle_id' => 'puzzle_test',
            'ordre_reponse' => 1,
            'coup' => 'Qh7#',
        ]);

        DB::table('dammier_indice')->insert([
            'identifiant_indice' => 'indice_test_1',
            'dammier_puzzle_id' => 'puzzle_test',
            'ordre_indice' => 1,
            'texte_indice' => 'Chercher sur la colonne h.',
        ]);

        DB::table('dammier_score')->insert([
            'dammier_score_id' => 'score_test',
            'dammier_week_key' => '2026-W23',
            'dammier_puzzle_id' => 'puzzle_test',
            'dammier_user_id' => 'utilisateur_test',
            'dammier_moves_count' => 2,
            'dammier_elapsed_seconds' => 35,
            'dammier_solved_at' => '2026-06-03 14:05:00',
        ]);

        $article = (new ArticleRepository())->trouverParIdentifiant('article_test');
        self::assertSame('Jeanne Dupont', $article['nom_auteur']);
        self::assertSame('Jeanne Dupont', $article['auteur_affiche']);
        self::assertSame('Contenu plat', $article['contenu']);

        $media = (new MediaRepository())->trouverParIdentifiant('media_test');
        self::assertSame('Jeanne Dupont', $media['nom_auteur']);

        $commande = (new OrderRepository())->trouverParIdentifiant('commande_test');
        self::assertSame('Jeanne Dupont', $commande['nom_utilisateur']);

        $classement = (new DammierRepository())->listerClassementHebdomadaire('2026-W23', 'puzzle_test');
        self::assertSame('Jeanne Dupont', $classement[0]['dammier_display_name']);

        $puzzle = (new DammierRepository())->obtenirPuzzleHebdomadaire();
        self::assertSame(['Qh7#'], $puzzle['dammier_solution']);
        self::assertSame(['Qh7#'], $puzzle['dammier_replies']);
        self::assertSame(['Chercher sur la colonne h.'], $puzzle['dammier_hints']);

        $horaire = (new ScheduleRepository())->obtenir();
        self::assertNotSame('', (string) ($horaire['items'][0]['time'] ?? ''));

        $abonnement = (new NewsletterRepository())->inscrire(
            'newsletter@example.test',
            hash('sha256', '127.0.0.1'),
            'PHPUnit',
            'tests'
        );
        self::assertSame('actif', $abonnement['statut']);
    }

    private function colonneEstTexteLegacyPourDateNaissance(): bool
    {
        $colonne = DB::selectOne("
            SELECT data_type
            FROM information_schema.columns
            WHERE table_name = 'compte_membre'
              AND column_name = 'date_naissance'
        ");

        if ($colonne === null) {
            return true;
        }

        return strtolower((string) ($colonne->data_type ?? '')) === 'varchar';
    }
}
