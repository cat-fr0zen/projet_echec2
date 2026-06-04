<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ConstructeurPagesRepository;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ConstructeurAccueilTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_seeding_prepare_des_emplacements_interchangeables_pour_l_accueil(): void
    {
        $this->seed(DatabaseSeeder::class);

        self::assertDatabaseHas('constructeur_page_bloc', [
            'code_page' => 'accueil',
            'code_bloc' => 'bandeau_accueil',
            'libelle_bloc' => "Bandeau d'accueil",
            'ordre_affichage' => 1,
            'est_actif' => 1,
            'est_verrouille' => 1,
        ]);

        self::assertDatabaseHas('constructeur_page_bloc', [
            'code_page' => 'accueil',
            'code_bloc' => 'casse_tete_hebdomadaire',
            'libelle_bloc' => 'Casse-tete hebdomadaire',
            'ordre_affichage' => 2,
            'est_actif' => 1,
            'est_verrouille' => 1,
        ]);

        self::assertDatabaseHas('constructeur_page_bloc', [
            'code_page' => 'accueil',
            'code_bloc' => 'liens_utiles',
            'libelle_bloc' => 'Liens utiles',
            'est_verrouille' => 0,
        ]);
    }

    public function test_un_admin_peut_reordonner_les_blocs_mobiles_sans_bouger_les_blocs_verrouilles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $depot = new ConstructeurPagesRepository();
        $depot->mettreAJourBlocsAccueil([
            'bandeau_accueil' => ['ordre_affichage' => 9, 'est_actif' => false],
            'casse_tete_hebdomadaire' => ['ordre_affichage' => 8, 'est_actif' => false],
            'liens_utiles' => ['ordre_affichage' => 1, 'est_actif' => true],
            'mot_du_club' => ['ordre_affichage' => 2, 'est_actif' => true],
            'pieces_echecs' => ['ordre_affichage' => 4, 'est_actif' => false],
            'chiffres_du_club' => ['ordre_affichage' => 3, 'est_actif' => true],
        ]);

        $blocs = DB::table('constructeur_page_bloc')
            ->where('code_page', 'accueil')
            ->orderBy('ordre_affichage')
            ->get(['code_bloc', 'ordre_affichage', 'est_actif', 'est_verrouille'])
            ->map(static fn (object $bloc): array => (array) $bloc)
            ->all();

        self::assertSame(
            [
                'bandeau_accueil',
                'casse_tete_hebdomadaire',
                'liens_utiles',
                'mot_du_club',
                'chiffres_du_club',
                'pieces_echecs',
            ],
            array_map(static fn (array $bloc): string => (string) $bloc['code_bloc'], $blocs)
        );

        self::assertTrue((bool) $blocs[0]['est_actif']);
        self::assertTrue((bool) $blocs[1]['est_actif']);
        self::assertFalse((bool) $blocs[5]['est_actif']);
    }

    public function test_l_accueil_respecte_l_ordre_des_blocs_actifs_et_l_admin_voit_un_constructeur_simple(): void
    {
        $this->seed(DatabaseSeeder::class);

        $depot = new ConstructeurPagesRepository();
        $depot->mettreAJourBlocsAccueil([
            'liens_utiles' => ['ordre_affichage' => 1, 'est_actif' => true],
            'mot_du_club' => ['ordre_affichage' => 2, 'est_actif' => true],
            'pieces_echecs' => ['ordre_affichage' => 4, 'est_actif' => false],
            'chiffres_du_club' => ['ordre_affichage' => 3, 'est_actif' => true],
        ]);

        $administrateur = (new UserRepository())->creer($this->donneesCompte('admin@example.test', 'Alice', 'Admin'));
        $_SESSION['identifiant_utilisateur'] = (string) $administrateur['identifiant'];

        $reponseAdmin = $this->get('/admin');
        $reponseAccueil = $this->get('/');

        unset($_SESSION['identifiant_utilisateur']);

        $reponseAdmin->assertOk();
        $reponseAdmin->assertSeeText('Constructeur');
        $reponseAdmin->assertSeeText("Bandeau d'accueil");
        $reponseAdmin->assertSeeText('Liens utiles');
        $reponseAdmin->assertDontSeeText('hero_title');

        $reponseAccueil->assertOk();
        $reponseAccueil->assertDontSee('data-accueil-slot="pieces_echecs"', false);

        $contenu = $reponseAccueil->getContent();
        self::assertIsString($contenu);

        $positionLiens = strpos($contenu, 'data-accueil-slot="liens_utiles"');
        $positionMotClub = strpos($contenu, 'data-accueil-slot="mot_du_club"');
        $positionChiffres = strpos($contenu, 'data-accueil-slot="chiffres_du_club"');

        self::assertNotFalse($positionLiens);
        self::assertNotFalse($positionMotClub);
        self::assertNotFalse($positionChiffres);
        self::assertLessThan($positionMotClub, $positionLiens);
        self::assertLessThan($positionChiffres, $positionMotClub);
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
