<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CoursPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_compte_connecte_simple_ne_voit_pas_longlet_cours_et_ne_peut_pas_y_acceder(): void
    {
        $this->creerAdministrateur('admin-reference-cours@example.test');

        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Cours',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'cours-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/')
            ->assertOk()
            ->assertDontSee('href="/guide"', false);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/guide')
            ->assertRedirect('/')
            ->assertSessionHas('messages_flash.0.type', 'error');
    }

    public function test_la_page_cours_affiche_un_hub_avec_trois_entrees_principales_pour_un_admin(): void
    {
        $administrateur = $this->creerAdministrateur('cours-page-admin@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/guide')
            ->assertOk()
            ->assertSeeText('Cours')
            ->assertSeeText('Livrets')
            ->assertSeeText('Methodologie / strategie')
            ->assertSeeText('Pedagogie')
            ->assertSeeText('Progression')
            ->assertSee('href="/cours-livrets"', false)
            ->assertSee('href="/cours-seances"', false)
            ->assertSee('href="/cours-progression"', false)
            ->assertDontSeeText('Livrets A a E')
            ->assertDontSee('id="cours-cours"', false)
            ->assertDontSee('id="cours-methodologie"', false)
            ->assertDontSee('id="cours-strategie"', false);
    }

    public function test_la_page_livrets_n_affiche_que_les_niveaux_qui_ont_des_documents(): void
    {
        $administrateur = $this->creerAdministrateur('livret-page-admin@example.test');

        $this->ajouterDocumentCours([
            'identifiant_document' => 'document_livret_importe_a',
            'code_rubrique' => 'livrets',
            'titre_document' => 'Livret A',
            'nom_fichier_stocke' => 'cours_livret_a.pdf',
            'groupe_document' => 'Nouveaux livrets',
        ], (string) $administrateur['identifiant']);

        $this->ajouterDocumentCours([
            'identifiant_document' => 'document_livret_direct_c',
            'code_rubrique' => 'livret_c',
            'titre_document' => 'Exercices livret C',
            'nom_fichier_stocke' => 'cours_livret_c.pdf',
        ], (string) $administrateur['identifiant']);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/cours-livrets')
            ->assertOk()
            ->assertSeeText('Livrets')
            ->assertSeeText('Niveaux disponibles')
            ->assertSeeText('Retour a la page Cours')
            ->assertSee('href="/cours-livret-a"', false)
            ->assertSee('href="/cours-livret-c"', false)
            ->assertDontSee('href="/cours-livret-b"', false)
            ->assertDontSee('href="/cours-livret-d"', false)
            ->assertDontSee('href="/cours-livret-e"', false);
    }

    public function test_un_livret_peut_afficher_les_pdf_importes_depuis_la_bibliotheque(): void
    {
        $administrateur = $this->creerAdministrateur('livret-detail-admin@example.test');

        $this->ajouterDocumentCours([
            'identifiant_document' => 'document_livret_a_importe',
            'code_rubrique' => 'livrets',
            'titre_document' => 'Livret A',
            'nom_fichier_stocke' => 'cours_livret_a_importe.pdf',
            'groupe_document' => 'Anciens livrets',
        ], (string) $administrateur['identifiant']);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/cours-livret-a')
            ->assertOk()
            ->assertSeeText('Livret A')
            ->assertSeeText('Retour a la page Livrets')
            ->assertSeeText('Livret A')
            ->assertSeeText('Anciens livrets')
            ->assertSeeText('Livret A')
            ->assertSee('/fichiers/cours/cours_livret_a_importe.pdf', false);
    }

    public function test_la_page_progression_n_affiche_que_les_rubriques_remplies(): void
    {
        $administrateur = $this->creerAdministrateur('progression-admin@example.test');

        $this->ajouterDocumentCours([
            'identifiant_document' => 'document_strategie_remplie',
            'code_rubrique' => 'strategie',
            'titre_document' => 'Plans de jeu',
            'nom_fichier_stocke' => 'cours_strategie.pdf',
            'groupe_document' => 'Structures',
        ], (string) $administrateur['identifiant']);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/cours-progression')
            ->assertOk()
            ->assertSeeText('Methodologie / strategie')
            ->assertSeeText('Rubriques disponibles')
            ->assertDontSee('/cours-methodologie', false)
            ->assertSee('/cours-strategie', false);
    }

    public function test_la_page_cours_dediee_affiche_sa_rubrique_documentaire(): void
    {
        $administrateur = $this->creerAdministrateur('seance-admin@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/cours-seances')
            ->assertOk()
            ->assertSeeText('Cours')
            ->assertSeeText('Le fil des seances')
            ->assertSee('id="cours-cours"', false);
    }

    public function test_les_pages_de_cours_affichent_les_documents_importes_en_groupes(): void
    {
        $administrateur = $this->creerAdministrateur('admin-groupe-cours@example.test');

        $this->ajouterDocumentCours([
            'identifiant_document' => 'document_livrets_importe',
            'code_rubrique' => 'livrets',
            'titre_document' => 'Livret A',
            'nom_fichier_stocke' => 'cours_livret_a.pdf',
            'groupe_document' => 'Nouveaux livrets',
            'sous_groupe_document' => null,
        ], (string) $administrateur['identifiant']);

        $this->ajouterDocumentCours([
            'identifiant_document' => 'document_cours_importe',
            'code_rubrique' => 'cours',
            'titre_document' => "L'attaque double 1",
            'nom_fichier_stocke' => 'cours_attaque_double.pdf',
            'groupe_document' => 'Cours de tactique',
            'sous_groupe_document' => "L'attaque double",
        ], (string) $administrateur['identifiant']);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/cours-livrets')
            ->assertOk()
            ->assertSeeText('Bibliotheque des livrets')
            ->assertSeeText('Nouveaux livrets')
            ->assertSeeText('Livret A')
            ->assertSee('/fichiers/cours/cours_livret_a.pdf', false);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/cours-seances')
            ->assertOk()
            ->assertSeeText('Cours de tactique')
            ->assertSeeText("L'attaque double")
            ->assertSeeText("L'attaque double 1")
            ->assertSee('/fichiers/cours/cours_attaque_double.pdf', false);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function ajouterDocumentCours(array $document, string $identifiantAuteur): void
    {
        DB::table('document_cours')->insert([
            'identifiant_document' => (string) ($document['identifiant_document'] ?? 'document_test'),
            'code_rubrique' => (string) ($document['code_rubrique'] ?? 'cours'),
            'titre_document' => (string) ($document['titre_document'] ?? 'Document'),
            'description_document' => 'Document importe pour test',
            'nom_fichier_original' => (string) ($document['titre_document'] ?? 'document').'.pdf',
            'nom_fichier_stocke' => (string) ($document['nom_fichier_stocke'] ?? 'cours_test.pdf'),
            'chemin_fichier' => 'fichiers/cours/'.(string) ($document['nom_fichier_stocke'] ?? 'cours_test.pdf'),
            'type_mime' => 'application/pdf',
            'taille_octets' => 128,
            'groupe_document' => $document['groupe_document'] ?? null,
            'sous_groupe_document' => $document['sous_groupe_document'] ?? null,
            'chemin_source_interne' => $document['chemin_source_interne'] ?? null,
            'identifiant_auteur' => $identifiantAuteur,
            'cree_le' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function creerAdministrateur(string $courriel): array
    {
        $repository = new UserRepository();
        $utilisateur = $repository->creer([
            'nom' => 'Admin',
            'prenom' => 'Cours',
            'date_naissance' => '1990-05-06',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
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
}
