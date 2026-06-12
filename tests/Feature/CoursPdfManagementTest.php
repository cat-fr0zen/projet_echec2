<?php

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

final class CoursPdfManagementTest extends TestCase
{
    use RefreshDatabase;

    private string $dossierCoursTests;

    private string $dossierSourceImportTests;

    protected function setUp(): void
    {
        parent::setUp();

        $racineTemporaire = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'projet_echec2_tests_cours';
        $this->dossierCoursTests = $racineTemporaire.DIRECTORY_SEPARATOR.'cours-documents';
        $this->dossierSourceImportTests = $racineTemporaire.DIRECTORY_SEPARATOR.'cours-source';

        File::deleteDirectory($this->dossierCoursTests);
        File::deleteDirectory($this->dossierSourceImportTests);
        File::ensureDirectoryExists($this->dossierCoursTests);
        File::ensureDirectoryExists($this->dossierSourceImportTests);

        putenv('COURSE_UPLOADS_PATH='.$this->dossierCoursTests);
        $_ENV['COURSE_UPLOADS_PATH'] = $this->dossierCoursTests;
        $_SERVER['COURSE_UPLOADS_PATH'] = $this->dossierCoursTests;

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossierCoursTests);
        File::deleteDirectory($this->dossierSourceImportTests);

        putenv('COURSE_UPLOADS_PATH');
        unset($_ENV['COURSE_UPLOADS_PATH'], $_SERVER['COURSE_UPLOADS_PATH']);

        parent::tearDown();
    }

    public function test_un_prof_peut_televerser_un_pdf_dans_un_livret(): void
    {
        $professeur = $this->creerProfesseur('prof-cours@example.test');
        $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
        ])->get('/cours-livret-a')->assertOk();

        $jetonCsrf = (string) session()->token();

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/cours-livret-a', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'ajouter_document_cours',
            'page_redirection' => 'cours-livret-a',
            'rubrique_document_cours' => 'livret_a',
            'titre_document_cours' => 'Tactiques debutants',
            'description_document_cours' => 'Premier livret PDF',
            'fichier_document_cours' => UploadedFile::fake()->createWithContent('livret-a.pdf', '%PDF-1.4 test cours'),
        ]);

        $reponse->assertRedirect('/cours-livret-a#cours-livret-a');
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
        $this->assertFileExists($this->dossierCoursTests.DIRECTORY_SEPARATOR.$document->nom_fichier_stocke);
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

    public function test_un_pdf_importe_depuis_un_sous_dossier_peut_etre_telecharge(): void
    {
        $professeur = $this->creerProfesseur('prof-importe@example.test');
        $this->creerPdfSource($this->dossierCoursTests, ['livret', 'Nouveaux livrets'], 'Livret A.pdf');

        DB::table('document_cours')->insert([
            'identifiant_document' => 'document_importe_sous_dossier',
            'code_rubrique' => 'livrets',
            'titre_document' => 'Livret A',
            'description_document' => 'Document importe',
            'nom_fichier_original' => 'Livret A.pdf',
            'nom_fichier_stocke' => 'cours_import_livret_a.pdf',
            'chemin_fichier' => 'fichiers/cours/cours_import_livret_a.pdf',
            'type_mime' => 'application/pdf',
            'taille_octets' => 24,
            'groupe_document' => 'Nouveaux livrets',
            'sous_groupe_document' => null,
            'chemin_source_interne' => 'livret/Nouveaux livrets/Livret A.pdf',
            'identifiant_auteur' => (string) $professeur['identifiant'],
            'cree_le' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
        ])->get('/fichiers/cours/cours_import_livret_a.pdf')
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
        ])->post('/cours-seances', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'supprimer_document_cours',
            'page_redirection' => 'cours-seances',
            'identifiant_document_cours' => $document['identifiant_document'],
        ]);

        $reponse->assertRedirect('/cours-seances#cours-cours');

        $this->assertDatabaseMissing('document_cours', [
            'identifiant_document' => $document['identifiant_document'],
        ]);
        $this->assertFileDoesNotExist(storage_path('app/private/uploads/cours/'.$document['nom_fichier_stocke']));
    }

    public function test_un_prof_peut_modifier_un_pdf_de_cours_et_le_deplacer_dans_une_autre_rubrique(): void
    {
        $professeur = $this->creerProfesseur('prof-modification@example.test');
        $document = $this->creerDocumentCours('livret_a', (string) $professeur['identifiant'], 'ancien-livret.pdf');
        $jetonCsrf = 'jeton-cours-update';

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $professeur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/cours-livret-a', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'modifier_document_cours',
            'page_redirection' => 'cours-livret-a',
            'identifiant_document_cours' => $document['identifiant_document'],
            'rubrique_document_cours' => 'strategie',
            'titre_document_cours' => 'Plan de jeu avance',
            'description_document_cours' => 'Nouvelle version du PDF',
            'fichier_document_cours_remplacement' => UploadedFile::fake()->createWithContent('nouveau-plan.pdf', '%PDF-1.4 remplacement'),
        ]);

        $reponse->assertRedirect('/cours-strategie#cours-strategie');
        $reponse->assertSessionHas('messages_flash.0.type', 'success');

        $documentMisAJour = DB::table('document_cours')
            ->where('identifiant_document', $document['identifiant_document'])
            ->first();

        $this->assertNotNull($documentMisAJour);
        $this->assertSame('strategie', $documentMisAJour->code_rubrique);
        $this->assertSame('Plan de jeu avance', $documentMisAJour->titre_document);
        $this->assertSame('Nouvelle version du PDF', $documentMisAJour->description_document);
        $this->assertNotSame($document['nom_fichier_stocke'], $documentMisAJour->nom_fichier_stocke);
        $this->assertFileDoesNotExist($this->dossierCoursTests.DIRECTORY_SEPARATOR.$document['nom_fichier_stocke']);
        $this->assertFileExists($this->dossierCoursTests.DIRECTORY_SEPARATOR.$documentMisAJour->nom_fichier_stocke);
    }

    public function test_la_commande_importe_les_pdf_ranges_dans_des_dossiers_et_reste_idempotente(): void
    {
        $administrateur = $this->creerAdministrateur('admin-import@example.test');
        $dossierSource = $this->dossierSourceImportTests;

        $this->creerPdfSource($dossierSource, ['livret', 'Nouveaux livrets'], 'Livret A.pdf');
        $this->creerPdfSource($dossierSource, ['cours', 'Cours de tactique', "L'attaque double"], "L'attaque double 1.pdf");
        $this->creerPdfSource($dossierSource, ['strategie', 'Tableaux de mat', 'Mat du couloir'], 'Mat du couloir.pdf');

        $this->artisan('cours:importer-pdf', [
            '--source' => $dossierSource,
            '--auteur' => (string) $administrateur['identifiant'],
        ])->expectsOutputToContain('Import termine')
            ->assertExitCode(0);

        $this->assertDatabaseHas('document_cours', [
            'code_rubrique' => 'livrets',
            'groupe_document' => 'Nouveaux livrets',
            'titre_document' => 'Livret A',
        ]);

        $this->assertDatabaseHas('document_cours', [
            'code_rubrique' => 'cours',
            'groupe_document' => 'Cours de tactique',
            'sous_groupe_document' => "L'attaque double",
            'titre_document' => "L'attaque double 1",
        ]);

        $this->assertDatabaseHas('document_cours', [
            'code_rubrique' => 'strategie',
            'groupe_document' => 'Tableaux de mat',
            'sous_groupe_document' => 'Mat du couloir',
            'titre_document' => 'Mat du couloir',
        ]);

        $documentImporte = DB::table('document_cours')
            ->where('titre_document', 'Livret A')
            ->first();

        $this->assertNotNull($documentImporte);
        $this->assertSame('livret/Nouveaux livrets/Livret A.pdf', $documentImporte->chemin_source_interne);
        $this->assertNotSame('', (string) $documentImporte->nom_fichier_stocke);

        $this->artisan('cours:importer-pdf', [
            '--source' => $dossierSource,
            '--auteur' => (string) $administrateur['identifiant'],
        ])->assertExitCode(0);

        $this->assertSame(3, DB::table('document_cours')->count());
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
        $dossier = $this->dossierCoursTests;

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
            'groupe_document' => null,
            'sous_groupe_document' => null,
            'chemin_source_interne' => null,
            'identifiant_auteur' => $identifiantAuteur,
            'cree_le' => now()->format('Y-m-d H:i:s'),
        ];

        DB::table('document_cours')->insert($document);

        return $document;
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function creerPdfSource(string $racine, array $segments, string $nomFichier): void
    {
        $dossier = $racine.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $segments);

        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        file_put_contents($dossier.DIRECTORY_SEPARATOR.$nomFichier, '%PDF-1.4 test import cours');
    }
}
