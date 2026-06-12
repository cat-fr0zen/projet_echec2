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

    public function test_la_page_cours_affiche_un_hub_avec_trois_entrees_principales(): void
    {
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

    public function test_la_page_livrets_affiche_les_entrees_vers_les_niveaux_a_a_e(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Livret',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'livret-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-livrets')
            ->assertOk()
            ->assertSeeText('Livrets')
            ->assertSeeText('Choisir un niveau')
            ->assertSeeText('Retour a la page Cours')
            ->assertSee('href="/cours-livret-a"', false)
            ->assertSee('href="/cours-livret-e"', false);
    }

    public function test_un_livret_a_sa_propre_page_dediee(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Livret',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'livret-detail-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-livret-a')
            ->assertOk()
            ->assertSeeText('Livret A')
            ->assertSeeText('Retour a la page Livrets')
            ->assertSeeText('Choisir un autre livret')
            ->assertSee('href="/cours-livret-b"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('id="cours-livret-a"', false);
    }

    public function test_la_page_progression_affiche_les_liens_vers_methodologie_et_strategie(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Progression',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'progression-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-progression')
            ->assertOk()
            ->assertSeeText('Methodologie / strategie')
            ->assertSeeText('Deux pages dediees')
            ->assertSee('href="/cours-methodologie"', false)
            ->assertSee('href="/cours-strategie"', false);
    }

    public function test_la_page_cours_dediee_affiche_sa_rubrique_documentaire(): void
    {
        $utilisateur = (new UserRepository())->creer([
            'nom' => 'Seance',
            'prenom' => 'Test',
            'date_naissance' => '2001-05-06',
            'courriel' => 'seance-page@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $utilisateur['identifiant'],
        ])->get('/cours-seances')
            ->assertOk()
            ->assertSeeText('Cours')
            ->assertSeeText('Le fil des seances')
            ->assertSee('id="cours-cours"', false);
    }

    public function test_les_pages_de_cours_affichent_les_documents_importes_en_groupes(): void
    {
        $repository = new UserRepository();
        $utilisateur = $repository->creer([
            'nom' => 'Admin',
            'prenom' => 'Cours',
            'date_naissance' => '1990-05-06',
            'courriel' => 'admin-groupe-cours@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
        $administrateur = $repository->mettreAJourAcces(
            (string) $utilisateur['identifiant'],
            User::ROLE_ADMIN,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_ACTIVE
        ) ?? $utilisateur;

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
}
