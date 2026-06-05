<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\NewsletterActualiteMail;
use App\Mail\NewsletterConfirmationMail;
use App\Repositories\NewsletterRepository;
use App\Repositories\UserRepository;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class NewsletterFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        config()->set('app.url', 'http://127.0.0.1:8000');
    }

    public function test_le_formulaire_public_inscrit_un_abonne_et_envoie_un_email_de_confirmation(): void
    {
        Mail::fake();
        $jetonCsrf = 'jeton-newsletter-public';

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.20',
            'HTTP_USER_AGENT' => 'PHPUnit Newsletter Public',
        ])->withSession(['_token' => $jetonCsrf])
            ->post('/', [
                '_token' => $jetonCsrf,
                'jeton_csrf' => $jetonCsrf,
                'action' => 'newsletter_subscribe',
                'newsletter_email' => 'abonne@example.test',
                'newsletter_consentement' => '1',
            ])
            ->assertRedirect('/#footer-newsletter-title');

        self::assertDatabaseHas('newsletter_abonnement', [
            'courriel_normalise' => 'abonne@example.test',
            'code_statut' => 'actif',
        ]);

        Mail::assertSent(NewsletterConfirmationMail::class, function (NewsletterConfirmationMail $mail): bool {
            return $mail->hasTo('abonne@example.test');
        });

        self::assertDatabaseHas('newsletter_envoi', [
            'code_type_evenement' => 'confirmation',
            'code_statut_envoi' => 'envoye',
        ]);
    }

    public function test_le_lien_de_desabonnement_desactive_un_abonnement_et_supporte_lancien_lien(): void
    {
        $depotNewsletter = new NewsletterRepository();
        $abonnement = $depotNewsletter->inscrire(
            'desabonnement@example.test',
            hash('sha256', '127.0.0.50'),
            'PHPUnit Newsletter Unsubscribe',
            'footer'
        );
        $jeton = (string) ($abonnement['jeton_desabonnement'] ?? '');

        $this->get('/?newsletter_unsubscribe='.$jeton)
            ->assertRedirect('/newsletter/desabonnement/'.$jeton);

        $this->get('/newsletter/desabonnement/'.$jeton)
            ->assertRedirect('/#footer-newsletter-title');

        self::assertDatabaseHas('newsletter_abonnement', [
            'jeton_desabonnement' => $jeton,
            'code_statut' => 'desabonne',
        ]);
    }

    public function test_l_admin_peut_voir_le_suivi_newsletter_et_declencher_un_envoi_boutique(): void
    {
        Mail::fake();

        $depotNewsletter = new NewsletterRepository();
        $abonnementActif = $depotNewsletter->inscrire(
            'actif@example.test',
            hash('sha256', '127.0.0.60'),
            'PHPUnit Newsletter Active',
            'footer'
        );
        $abonnementDesabonne = $depotNewsletter->inscrire(
            'desabonne@example.test',
            hash('sha256', '127.0.0.61'),
            'PHPUnit Newsletter Unsubscribed',
            'footer'
        );
        $depotNewsletter->desabonner((string) ($abonnementDesabonne['jeton_desabonnement'] ?? ''));

        $administrateur = (new UserRepository())->creer([
            'nom' => 'Admin',
            'prenom' => 'Newsletter',
            'date_naissance' => '1985-03-04',
            'courriel' => 'admin-newsletter@example.test',
            'numero_licence' => '',
            'mot_de_passe' => 'Motdepasse2026!',
            'description_profil' => 'Admin newsletter',
            'pseudo_chess' => '',
        ]);

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
        ])->get('/admin')
            ->assertOk()
            ->assertSeeText('Newsletter')
            ->assertSeeText('actif@example.test')
            ->assertSeeText('desabonne@example.test');

        $jetonCsrf = 'jeton-newsletter-admin';

        $this->withSession([
            'identifiant_utilisateur' => (string) $administrateur['identifiant'],
            '_token' => $jetonCsrf,
        ])->post('/admin', [
            '_token' => $jetonCsrf,
            'jeton_csrf' => $jetonCsrf,
            'action' => 'notify_shop_item',
            'titre_objet_boutique' => 'Nouveau polo du club',
        ])->assertRedirect('/admin#admin-newsletter-boutique');

        Mail::assertSent(NewsletterActualiteMail::class, 1);
        Mail::assertSent(NewsletterActualiteMail::class, function (NewsletterActualiteMail $mail): bool {
            return $mail->hasTo('actif@example.test');
        });
        Mail::assertNotSent(NewsletterActualiteMail::class, function (NewsletterActualiteMail $mail): bool {
            return $mail->hasTo('desabonne@example.test');
        });

        self::assertDatabaseHas('newsletter_envoi', [
            'identifiant_abonnement' => (string) $abonnementActif['identifiant_abonnement'],
            'code_type_evenement' => 'boutique',
            'code_statut_envoi' => 'envoye',
        ]);
    }
}
