<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AdminCourseShopManagementTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class AdminCourseShopManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $dossierCoursTests;

    protected function setUp(): void
    {
        parent::setUp();

        $racineTemporaire = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'projet_echec2_tests_admin';
        $this->dossierCoursTests = $racineTemporaire.DIRECTORY_SEPARATOR.'cours-documents';

        File::deleteDirectory($this->dossierCoursTests);
        File::ensureDirectoryExists($this->dossierCoursTests);

        putenv('COURSE_UPLOADS_PATH='.$this->dossierCoursTests);
        $_ENV['COURSE_UPLOADS_PATH'] = $this->dossierCoursTests;
        $_SERVER['COURSE_UPLOADS_PATH'] = $this->dossierCoursTests;

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossierCoursTests);

        putenv('COURSE_UPLOADS_PATH');
        unset($_ENV['COURSE_UPLOADS_PATH'], $_SERVER['COURSE_UPLOADS_PATH']);

        parent::tearDown();
    }

    public function test_la_page_admin_affiche_des_sections_pour_gerer_les_cours_et_la_boutique(): void
    {
        $administrateur = $this->creerAdministrateur('admin-gestion@example.test');

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/admin');

        $reponse->assertOk();
        $reponse->assertSee('Gerer les PDF de cours', false);
        $reponse->assertSee('Gerer le catalogue de la boutique', false);
        $reponse->assertSee('Ajouter un produit', false);
        $reponse->assertSee('Lien HelloAsso unique', false);
        $reponse->assertSee('Gerer les cartes du bureau visibles sur l', false);
    }

    public function test_un_admin_peut_ajouter_un_document_de_cours_depuis_la_page_admin(): void
    {
        $administrateur = $this->creerAdministrateur('admin-cours-dashboard@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/admin')->assertOk();

        $jetonCsrf = (string) session()->token();

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'ajouter_document_cours',
            'page_redirection' => 'admin',
            'rubrique_document_cours' => 'cours',
            'titre_document_cours' => 'Preparation ouverture',
            'description_document_cours' => 'Support admin centralise',
            'fichier_document_cours' => UploadedFile::fake()->createWithContent('ouverture.pdf', '%PDF-1.4 test admin cours'),
        ]);

        $reponse->assertRedirect('/admin#admin-cours');
        $reponse->assertSessionHas('messages_flash.0.type', 'success');

        $this->assertDatabaseHas('document_cours', [
            'code_rubrique' => 'cours',
            'titre_document' => 'Preparation ouverture',
            'identifiant_auteur' => (string) $administrateur['identifiant'],
        ]);
    }

    public function test_un_admin_peut_creer_modifier_et_supprimer_un_produit_boutique_depuis_l_admin(): void
    {
        $administrateur = $this->creerAdministrateur('admin-boutique@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/admin')->assertOk();

        $jetonCsrf = (string) session()->token();

        $creation = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'ajouter_produit_boutique',
            'reference_produit_boutique' => 'TEXT-001',
            'titre_produit_boutique' => 'Sweat du club',
            'categorie_produit_boutique' => 'textile',
            'public_produit_boutique' => 'tous',
            'prix_produit_boutique' => '45',
            'badge_produit_boutique' => 'Nouveau',
            'mode_vente_produit_boutique' => 'reservation',
            'description_produit_boutique' => 'Sweat officiel a capuche.',
            'resume_produit_boutique' => 'Logo brode et coupe mixte.',
            'avantages_produit_boutique' => "Tissu epais\nLogo brode",
            'ordre_affichage_produit_boutique' => '2',
            'stock_produit_boutique' => '1',
            'visible_produit_boutique' => '1',
        ]);

        $creation->assertRedirect('/admin#admin-boutique');
        $this->assertDatabaseHas('boutique_produit', [
            'reference_produit' => 'TEXT-001',
            'titre_produit' => 'Sweat du club',
            'prix_euros' => 4500,
        ]);

        $identifiantProduit = (string) DB::table('boutique_produit')
            ->where('reference_produit', 'TEXT-001')
            ->value('identifiant_produit');

        $this->assertNotSame('', $identifiantProduit);

        $modification = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'modifier_produit_boutique',
            'identifiant_produit_boutique' => $identifiantProduit,
            'reference_produit_boutique' => 'TEXT-001',
            'titre_produit_boutique' => 'Sweat premium du club',
            'categorie_produit_boutique' => 'textile',
            'public_produit_boutique' => 'membre',
            'prix_produit_boutique' => '49',
            'badge_produit_boutique' => 'Edition club',
            'mode_vente_produit_boutique' => 'precommande',
            'description_produit_boutique' => 'Version epaisse pour tournoi.',
            'resume_produit_boutique' => 'Commande reservee aux membres.',
            'avantages_produit_boutique' => "Polaire\nCapuche",
            'ordre_affichage_produit_boutique' => '1',
            'stock_produit_boutique' => '0',
            'visible_produit_boutique' => '1',
        ]);

        $modification->assertRedirect('/admin#admin-boutique');
        $this->assertDatabaseHas('boutique_produit', [
            'identifiant_produit' => $identifiantProduit,
            'titre_produit' => 'Sweat premium du club',
            'public_cible' => 'membre',
            'prix_euros' => 4900,
            'mode_vente' => 'precommande',
        ]);

        $suppression = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'supprimer_produit_boutique',
            'identifiant_produit_boutique' => $identifiantProduit,
        ]);

        $suppression->assertRedirect('/admin#admin-boutique');
        $this->assertDatabaseMissing('boutique_produit', [
            'identifiant_produit' => $identifiantProduit,
        ]);
    }

    public function test_un_admin_peut_modifier_le_lien_helloasso_utilise_par_toute_la_boutique(): void
    {
        $administrateur = $this->creerAdministrateur('admin-helloasso@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/admin')->assertOk();

        $jetonCsrf = (string) session()->token();
        $lienHelloAsso = 'https://www.helloasso.com/associations/les-cavaliers-d-herouville/collectes/boutique-admin';

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'mettre_a_jour_lien_helloasso_boutique',
            'lien_helloasso_boutique' => $lienHelloAsso,
        ]);

        $reponse->assertRedirect('/admin#admin-boutique');

        $this->assertDatabaseHas('parametre_site', [
            'cle_parametre' => 'lien_boutique_helloasso',
            'valeur_texte' => $lienHelloAsso,
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/boutique')
            ->assertOk()
            ->assertSee($lienHelloAsso, false);
    }

    public function test_un_admin_peut_modifier_les_textes_du_bureau_et_gerer_les_cartes(): void
    {
        $administrateur = $this->creerAdministrateur('admin-bureau@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/admin')->assertOk();

        $jetonCsrf = (string) session()->token();

        $miseAJourTextes = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'mettre_a_jour_textes_bureau',
            'bureau_surtitre' => 'Equipe dirigeante',
            'bureau_titre' => 'Les responsables du club',
            'bureau_description' => 'Presentation modifiable du bureau.',
        ]);

        $miseAJourTextes->assertRedirect('/admin#admin-bureau-club');
        $this->assertDatabaseHas('parametre_site', [
            'cle_parametre' => 'bureau_section_titre',
            'valeur_texte' => 'Les responsables du club',
        ]);

        $ajout = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'ajouter_membre_bureau',
            'prenom_membre_bureau' => 'Claire',
            'nom_membre_bureau' => 'DUPONT',
            'role_membre_bureau' => 'Secretaire',
            'description_membre_bureau' => 'Coordonne les echanges administratifs du club.',
            'photo_membre_bureau' => '',
            'ordre_affichage_membre_bureau' => '4',
            'visible_membre_bureau' => '1',
        ]);

        $ajout->assertRedirect('/admin#admin-bureau-club');

        $identifiantMembreBureau = (string) DB::table('bureau_membre')
            ->where('prenom', 'Claire')
            ->value('identifiant_membre_bureau');

        $this->assertNotSame('', $identifiantMembreBureau);

        $miseAJourCarte = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'modifier_membre_bureau',
            'identifiant_membre_bureau' => $identifiantMembreBureau,
            'prenom_membre_bureau' => 'Claire',
            'nom_membre_bureau' => 'DUPONT',
            'role_membre_bureau' => 'Secretaire generale',
            'description_membre_bureau' => 'Suit les documents et la communication interne.',
            'photo_membre_bureau' => '',
            'ordre_affichage_membre_bureau' => '2',
            'visible_membre_bureau' => '1',
        ]);

        $miseAJourCarte->assertRedirect('/admin#admin-bureau-club');
        $this->assertDatabaseHas('bureau_membre', [
            'identifiant_membre_bureau' => $identifiantMembreBureau,
            'role_affiche' => 'Secretaire generale',
            'ordre_affichage' => 2,
        ]);

        $suppression = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'supprimer_membre_bureau',
            'identifiant_membre_bureau' => 'bureau_francois',
        ]);

        $suppression->assertRedirect('/admin#admin-bureau-club');
        $this->assertDatabaseMissing('bureau_membre', [
            'identifiant_membre_bureau' => 'bureau_francois',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Equipe dirigeante', false)
            ->assertSee('Les responsables du club', false)
            ->assertSee('Secretaire generale', false)
            ->assertDontSee('Francois', false);
    }

    public function test_la_page_boutique_affiche_les_produits_enregistres_en_base_pour_un_compte_connecte(): void
    {
        $administrateur = $this->creerAdministrateur('admin-boutique-public@example.test');
        $this->creerProduitBoutique([
            'identifiant_produit' => 'produit_textile_test',
            'reference_produit' => 'TEXT-777',
            'titre_produit' => 'Polo du club',
            'categorie_produit' => 'textile',
            'public_cible' => 'tous',
            'prix_euros' => 2800,
            'badge' => 'Club',
            'mode_vente' => 'reservation',
            'texte_produit' => 'Polo brode pour les evenements.',
            'resume_produit' => 'Disponible en plusieurs tailles.',
            'avantages_json' => json_encode(['Broderie', 'Coton'], JSON_THROW_ON_ERROR),
            'ordre_affichage' => 1,
            'est_en_stock' => 1,
            'est_actif' => 1,
            'identifiant_auteur' => (string) $administrateur['identifiant'],
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/boutique')
            ->assertOk()
            ->assertSee('Polo du club', false)
            ->assertSee('TEXT-777', false)
            ->assertSee('28,00 €', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function creerAdministrateur(string $courriel): array
    {
        $repository = new UserRepository();
        $utilisateur = $repository->creer([
            'nom' => 'Admin',
            'prenom' => 'Gestion',
            'date_naissance' => '1990-01-02',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        return $repository->mettreAJourAcces(
            (string) $utilisateur['identifiant'],
            User::ROLE_ADMIN,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_ACTIVE
        ) ?? $utilisateur;
    }

    /**
     * @param  array<string, mixed>  $attributs
     */
    private function creerProduitBoutique(array $attributs): void
    {
        DB::table('boutique_produit')->insert([
            'identifiant_produit' => (string) ($attributs['identifiant_produit'] ?? 'produit_test'),
            'reference_produit' => (string) ($attributs['reference_produit'] ?? 'TEST-001'),
            'titre_produit' => (string) ($attributs['titre_produit'] ?? 'Produit test'),
            'categorie_produit' => (string) ($attributs['categorie_produit'] ?? 'materiel'),
            'public_cible' => (string) ($attributs['public_cible'] ?? 'tous'),
            'prix_euros' => (int) ($attributs['prix_euros'] ?? 0),
            'badge' => (string) ($attributs['badge'] ?? ''),
            'mode_vente' => (string) ($attributs['mode_vente'] ?? 'reservation'),
            'texte_produit' => (string) ($attributs['texte_produit'] ?? ''),
            'resume_produit' => (string) ($attributs['resume_produit'] ?? ''),
            'avantages_json' => (string) ($attributs['avantages_json'] ?? '[]'),
            'ordre_affichage' => (int) ($attributs['ordre_affichage'] ?? 1),
            'est_en_stock' => (int) ($attributs['est_en_stock'] ?? 1),
            'est_actif' => (int) ($attributs['est_actif'] ?? 1),
            'identifiant_auteur' => (string) ($attributs['identifiant_auteur'] ?? ''),
            'cree_le' => now()->format('Y-m-d H:i:s'),
            'mis_a_jour_le' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
