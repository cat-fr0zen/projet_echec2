<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CommandeLocale;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BoutiqueCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_un_membre_peut_ajouter_un_produit_au_panier_depuis_la_boutique(): void
    {
        $membre = $this->creerMembre('panier-boutique@example.test');
        $jetonCsrf = 'jeton-boutique-panier';

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/boutique', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'ajouter_au_panier',
            'identifiant_produit' => 'polo-club',
        ]);

        $reponse->assertRedirect('/boutique#boutique-panier');
        $reponse->assertSessionHas('panier_boutique', function (mixed $panier): bool {
            return is_array($panier)
                && (int) (($panier['polo-club']['quantite'] ?? 0)) === 1;
        });

        $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            'panier_boutique' => session('panier_boutique'),
        ])->get('/boutique')
            ->assertOk()
            ->assertSeeText('Panier et paiement')
            ->assertSeeText('Polo officiel du club')
            ->assertSeeText('32 EUR');
    }

    public function test_la_finalisation_du_panier_cree_des_commandes_avec_quantite_total_et_mode_paiement(): void
    {
        $membre = $this->creerMembre('checkout-boutique@example.test');
        $jetonCsrf = 'jeton-boutique-checkout';

        $reponse = $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            '_token' => $jetonCsrf,
            'panier_boutique' => [
                'polo-club' => ['quantite' => 2],
                'gourde-club' => ['quantite' => 1],
            ],
        ])->post('/boutique', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'valider_panier',
            'mode_paiement' => CommandeLocale::MODE_PAIEMENT_CARTE_BANCAIRE,
        ]);

        $reponse->assertRedirect('/boutique#boutique-commandes');
        $reponse->assertSessionMissing('panier_boutique');

        $this->assertDatabaseHas('commande_locale', [
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            'reference_produit' => 'polo-club',
            'quantite' => 2,
            'prix_unitaire_euros' => 32,
            'prix_total_euros' => 64,
            'code_mode_paiement' => CommandeLocale::MODE_PAIEMENT_CARTE_BANCAIRE,
            'code_statut_paiement' => CommandeLocale::STATUT_PAIEMENT_EN_ATTENTE_PRESTATAIRE,
        ]);

        $this->assertDatabaseHas('commande_locale', [
            'identifiant_utilisateur' => (string) $membre['identifiant'],
            'reference_produit' => 'gourde-club',
            'quantite' => 1,
            'prix_unitaire_euros' => 14,
            'prix_total_euros' => 14,
            'code_mode_paiement' => CommandeLocale::MODE_PAIEMENT_CARTE_BANCAIRE,
            'code_statut_paiement' => CommandeLocale::STATUT_PAIEMENT_EN_ATTENTE_PRESTATAIRE,
        ]);
    }

    public function test_la_page_boutique_prepare_le_paiement_cb_sans_collecter_de_numero_de_carte(): void
    {
        $membre = $this->creerMembre('cb-boutique@example.test');

        $this->withSession([
            'identifiant_utilisateur' => (string) $membre['identifiant'],
        ])->get('/boutique')
            ->assertOk()
            ->assertSeeText('Carte bancaire')
            ->assertDontSee('name="numero_carte"', false)
            ->assertDontSee('name="cvv"', false)
            ->assertDontSee('name="cryptogramme"', false);
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
}
