<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : AdminRolesEtTraficTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use Database\Seeders\ReferenceTablesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class AdminRolesEtTraficTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_role_prof_est_reference_et_limite_a_dix_comptes(): void
    {
        $this->seed(ReferenceTablesSeeder::class);

        self::assertDatabaseHas('ref_role_compte', [
            'code_role' => 'prof',
        ]);

        $depot = new UserRepository();
        $depot->creer($this->donneesCompte('admin@example.test', 'Admin', 'Premier'));

        $cibles = [];

        for ($index = 1; $index <= 11; $index++) {
            $cibles[] = $depot->creer(
                $this->donneesCompte("prof{$index}@example.test", "Prof{$index}", 'Candidat')
            );
        }

        foreach (array_slice($cibles, 0, 10) as $utilisateur) {
            self::assertNotNull(
                $depot->mettreAJourAcces(
                    (string) $utilisateur['identifiant'],
                    'prof',
                    'actif',
                    'active'
                )
            );
        }

        self::assertNull(
            $depot->mettreAJourAcces(
                (string) $cibles[10]['identifiant'],
                'prof',
                'actif',
                'active'
            )
        );

        self::assertSame(10, DB::table('compte_membre')->where('code_role', 'prof')->count());
    }

    public function test_un_admin_peut_transferer_son_role_a_un_autre_compte(): void
    {
        $this->seed(ReferenceTablesSeeder::class);

        $depot = new UserRepository();
        $administrateur = $depot->creer($this->donneesCompte('admin@example.test', 'Alice', 'Admin'));
        $cible = $depot->creer($this->donneesCompte('cible@example.test', 'Bruno', 'Cible'));

        self::assertSame('admin', $administrateur['role']);
        self::assertSame('connecte', $cible['role']);

        $transfert = $depot->transfererRoleAdmin(
            (string) $administrateur['identifiant'],
            (string) $cible['identifiant'],
            'prof'
        );

        self::assertNotNull($transfert);
        self::assertSame('admin', $transfert['role']);
        $ancienAdministrateur = $depot->trouverParIdentifiant((string) $administrateur['identifiant']) ?? [];
        self::assertSame(
            'prof',
            (string) ($ancienAdministrateur['role'] ?? '')
        );
    }

    public function test_une_visite_invitée_est_journalisee_mais_pas_une_visite_connectee(): void
    {
        $this->seed(ReferenceTablesSeeder::class);

        $depot = new UserRepository();
        $administrateur = $depot->creer($this->donneesCompte('admin@example.test', 'Alice', 'Admin'));

        self::assertDatabaseCount('journal_visite_visiteur', 0);

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.10',
            'HTTP_USER_AGENT' => 'PHPUnit Guest',
            'HTTP_REFERER' => 'https://moteur.example.test/recherche',
        ])->get('/')->assertOk();

        self::assertDatabaseCount('journal_visite_visiteur', 1);

        $_SESSION['identifiant_utilisateur'] = (string) $administrateur['identifiant'];

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.11',
            'HTTP_USER_AGENT' => 'PHPUnit Admin',
        ])->get('/')->assertOk();

        unset($_SESSION['identifiant_utilisateur']);

        self::assertDatabaseCount('journal_visite_visiteur', 1);
    }

    public function test_le_dashboard_admin_affiche_le_role_prof_et_un_resume_du_trafic_visiteur(): void
    {
        $this->seed(ReferenceTablesSeeder::class);

        $depot = new UserRepository();
        $administrateur = $depot->creer($this->donneesCompte('admin@example.test', 'Alice', 'Admin'));

        DB::table('journal_visite_visiteur')->insert([
            [
                'identifiant_visite' => 'visite_1',
                'page' => 'accueil',
                'hachage_session' => hash('sha256', 'session_1'),
                'hachage_ip' => hash('sha256', 'ip_1'),
                'hote_referent' => 'moteur.example.test',
                'agent_utilisateur' => 'Mozilla/5.0',
                'visite_le' => '2026-06-03 09:00:00',
            ],
            [
                'identifiant_visite' => 'visite_2',
                'page' => 'articles',
                'hachage_session' => hash('sha256', 'session_2'),
                'hachage_ip' => hash('sha256', 'ip_2'),
                'hote_referent' => 'reseau.example.test',
                'agent_utilisateur' => 'Mozilla/5.0',
                'visite_le' => '2026-06-03 10:00:00',
            ],
        ]);

        $_SESSION['identifiant_utilisateur'] = (string) $administrateur['identifiant'];

        $reponse = $this->get('/admin');

        unset($_SESSION['identifiant_utilisateur']);

        $reponse->assertOk();
        $reponse->assertSeeText('Prof');
        $reponse->assertSeeText('Trafic visiteurs');
        $reponse->assertSeeText('moteur.example.test');
        $reponse->assertSeeText('reseau.example.test');
    }

    public function test_une_page_publique_reste_affichable_si_la_base_est_indisponible(): void
    {
        DB::shouldReceive('table')->andThrow(new RuntimeException('base indisponible'));

        $this->get('/mediatheque')
            ->assertOk()
            ->assertSeeText('Connexion requise');
    }

    /**
     * @return array<string, string>
     */
    private function donneesCompte(string $courriel, string $prenom, string $nom): array
    {
        return [
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => '2000-01-02',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'motdepasse-solide',
            'description_profil' => 'Compte de test',
            'pseudo_chess' => '',
        ];
    }
}
