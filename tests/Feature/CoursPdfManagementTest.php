<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CoursPdfManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_prof_peut_televerser_un_pdf_dans_un_livret(): void
    {
        $professeur = $this->creerProfesseur('prof-cours@example.test');
        $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
        ])->get('/guide')->assertOk();

        $jetonCsrf = (string) session()->token();

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/guide', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'ajouter_document_cours',
            'page_redirection' => 'guide',
            'rubrique_document_cours' => 'livret_a',
            'titre_document_cours' => 'Tactiques debutants',
            'description_document_cours' => 'Premier livret PDF',
            'fichier_document_cours' => UploadedFile::fake()->createWithContent('livret-a.pdf', '%PDF-1.4 test cours'),
        ]);

        $reponse->assertRedirect('/guide#cours-livret-a');
        $reponse->assertSessionHas('messages_flash.0.type', 'success');

        $this->assertDatabaseHas('document_cours', [
            'code_rubrique' => 'livret_a',
            'titre_document' => 'Tactiques debutants',
            'identifiant_auteur' => (string) $professeur['identifiant'],
        ]);

        $document = DB::table('document_cours')
            ->where('titre_document', 'Tactiques debutants')
            ->first();

        $this->assertNotNull($document);
        $this->assertFileExists(storage_path('app/private/uploads/cours/'.$document->nom_fichier_stocke));
    }

    public function test_un_prof_peut_telecharger_un_pdf_de_cours(): void
    {
        $professeur = $this->creerProfesseur('prof-telechargement@example.test');
        $document = $this->creerDocumentCours('livret_b', (string) $professeur['identifiant'], 'livret-b.pdf');

        $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
        ])->get('/fichiers/cours/'.$document['nom_fichier_stocke'])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_un_compte_connecte_simple_ne_peut_pas_telecharger_un_pdf_de_cours(): void
    {
        $professeur = $this->creerProfesseur('prof-prive@example.test');
        $visiteurConnecte = $this->creerUtilisateur('membre-cours@example.test');
        $document = $this->creerDocumentCours('livret_c', (string) $professeur['identifiant'], 'livret-c.pdf');

        $this->withSession([
            'identifiant_utilisateur' => (string) $visiteurConnecte['identifiant'],
        ])->get('/fichiers/cours/'.$document['nom_fichier_stocke'])
            ->assertNotFound();
    }

    public function test_un_admin_peut_supprimer_un_pdf_de_cours(): void
    {
        $administrateur = $this->creerAdministrateur('admin-cours@example.test');
        $document = $this->creerDocumentCours('cours', (string) $administrateur['identifiant'], 'cours.pdf');
        $jetonCsrf = 'jeton-cours-delete';

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/guide', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'supprimer_document_cours',
            'page_redirection' => 'guide',
            'identifiant_document_cours' => $document['identifiant_document'],
        ]);

        $reponse->assertRedirect('/guide#cours-cours');

        $this->assertDatabaseMissing('document_cours', [
            'identifiant_document' => $document['identifiant_document'],
        ]);
        $this->assertFileDoesNotExist(storage_path('app/private/uploads/cours/'.$document['nom_fichier_stocke']));
    }

    /**
     * @return array<string, mixed>
     */
    private function creerAdministrateur(string $courriel): array
    {
        return (new UserRepository())->creer([
            'nom' => 'Admin',
            'prenom' => 'Cours',
            'date_naissance' => '1990-01-02',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function creerProfesseur(string $courriel): array
    {
        $administrateur = $this->creerAdministrateur('admin-professeur@example.test');
        $professeur = $this->creerUtilisateur($courriel);
        $repository = new UserRepository();

        return $repository->mettreAJourAcces(
            (string) $professeur['identifiant'],
            User::ROLE_PROF,
            User::STATUT_COMPTE_ACTIF,
            User::STATUT_ADHESION_ACTIVE
        ) ?? $administrateur;
    }

    /**
     * @return array<string, mixed>
     */
    private function creerUtilisateur(string $courriel): array
    {
        return (new UserRepository())->creer([
            'nom' => 'Membre',
            'prenom' => 'Cours',
            'date_naissance' => '1992-03-04',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function creerDocumentCours(string $rubrique, string $identifiantAuteur, string $nomOriginal): array
    {
        $dossier = storage_path('app/private/uploads/cours');

        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $nomStocke = 'cours_'.md5($rubrique.$nomOriginal).'.pdf';
        file_put_contents($dossier.DIRECTORY_SEPARATOR.$nomStocke, '%PDF-1.4 test cours');

        $document = [
            'identifiant_document' => 'document_'.substr(md5($nomStocke), 0, 12),
            'code_rubrique' => $rubrique,
            'titre_document' => 'Document '.$rubrique,
            'description_document' => 'Document de test',
            'nom_fichier_original' => $nomOriginal,
            'nom_fichier_stocke' => $nomStocke,
            'chemin_fichier' => 'fichiers/cours/'.$nomStocke,
            'type_mime' => 'application/pdf',
            'taille_octets' => 18,
            'identifiant_auteur' => $identifiantAuteur,
            'cree_le' => now()->format('Y-m-d H:i:s'),
        ];

        DB::table('document_cours')->insert($document);

        return $document;
    }
}
