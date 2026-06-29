<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : BoutiqueCartTest.
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BoutiqueCartTest extends TestCase
{
    use RefreshDatabase;

    private const LIEN_HELLOASSO_PAR_DEFAUT = 'https://www.helloasso.com/associations/les-cavaliers-d-herouville';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_compte_connecte_peut_ouvrir_la_boutique_et_voir_le_lien_helloasso_unique(): void
    {
        $membre = $this->creerMembre('helloasso-boutique@example.test');
        $this->creerProduitBoutique([
            'identifiant_produit' => 'polo-club',
            'reference_produit' => 'TEXT-POLO',
            'titre_produit' => 'Polo officiel du club',
            'categorie_produit' => 'textile',
            'public_cible' => 'tous',
            'prix_euros' => 32,
            'badge' => 'Club',
            'mode_vente' => 'reservation',
            'texte_produit' => 'Polo brode pour les rencontres du club.',
            'resume_produit' => 'Coupe mixte et tissu respirant.',
            'avantages_json' => '["Broderie","Tissu respirant"]',
            'identifiant_auteur' => (string) $membre['identifiant'],
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
        ])->get('/boutique')
            ->assertOk()
            ->assertSee('Polo officiel du club', false)
            ->assertSee('Ouvrir sur HelloAsso', false)
            ->assertSee(self::LIEN_HELLOASSO_PAR_DEFAUT, false)
            ->assertDontSee('Panier et paiement', false)
            ->assertDontSee('Ajouter au panier', false)
            ->assertDontSee('Continuer vers le paiement CB', false);
    }

    public function test_un_ancien_post_de_validation_redirige_vers_helloasso_sans_creer_de_commande(): void
    {
        $membre = $this->creerMembre('checkout-helloasso@example.test');
        $this->creerProduitBoutique([
            'identifiant_produit' => 'polo-club',
            'reference_produit' => 'TEXT-POLO',
            'titre_produit' => 'Polo officiel du club',
            'categorie_produit' => 'textile',
            'public_cible' => 'tous',
            'prix_euros' => 32,
            'badge' => 'Club',
            'mode_vente' => 'reservation',
            'texte_produit' => 'Polo brode pour les rencontres du club.',
            'resume_produit' => 'Coupe mixte et tissu respirant.',
            'avantages_json' => '["Broderie","Tissu respirant"]',
            'identifiant_auteur' => (string) $membre['identifiant'],
        ]);
        $jetonCsrf = 'jeton-boutique-helloasso';

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            '_token' => $jetonCsrf,
            'panier_boutique' => [
                'polo-club' => ['quantite' => 2],
            ],
        ])->post('/boutique', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'checkout_cart',
        ]);

        $reponse->assertRedirect(self::LIEN_HELLOASSO_PAR_DEFAUT);

        $this->assertDatabaseMissing('commande_locale', [
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            'reference_produit' => 'polo-club',
        ]);
    }

    public function test_la_page_boutique_utilise_le_lien_helloasso_personnalise_enregistre_en_base(): void
    {
        $membre = $this->creerMembre('helloasso-personnalise@example.test');
        $this->creerProduitBoutique([
            'identifiant_produit' => 'gourde-club',
            'reference_produit' => 'ACC-GOURDE',
            'titre_produit' => 'Gourde du club',
            'categorie_produit' => 'accessoire',
            'public_cible' => 'tous',
            'prix_euros' => 14,
            'badge' => 'Accessoire',
            'mode_vente' => 'reservation',
            'texte_produit' => 'Gourde siglee pour les deplacements.',
            'resume_produit' => 'Format 500 ml.',
            'avantages_json' => '["Legere","Reutilisable"]',
            'identifiant_auteur' => (string) $membre['identifiant'],
        ]);

        $lienPersonnalise = 'https://www.helloasso.com/associations/les-cavaliers-d-herouville/collectes/boutique-stage';

        DB::table('parametre_site')->insert([
            'cle_parametre' => 'lien_boutique_helloasso',
            'valeur_texte' => $lienPersonnalise,
            'cree_le' => now()->format('Y-m-d H:i:s'),
            'mis_a_jour_le' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
        ])->get('/boutique')
            ->assertOk()
            ->assertSee($lienPersonnalise, false)
            ->assertDontSee('href="'.self::LIEN_HELLOASSO_PAR_DEFAUT.'"', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function creerMembre(string $courriel): array
    {
        return (new UserRepository())->creer([
            'nom' => 'Boutique',
            'prenom' => 'Test',
            'date_naissance' => '2000-02-03',
            'courriel' => $courriel,
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => '',
            'pseudo_chess' => '',
        ]);
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
