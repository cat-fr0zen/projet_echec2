<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : NewsletterMailerService.
 */

declare(strict_types=1);

namespace App\Services;

use App\Mail\NewsletterActualiteMail;
use App\Mail\NewsletterConfirmationMail;
use App\Repositories\NewsletterRepository;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class NewsletterMailerService
{
    public const TYPE_ARTICLE = 'article';
    public const TYPE_COURS = 'cours';
    public const TYPE_BOUTIQUE = 'boutique';
    public const TYPE_EVENEMENT = 'evenement';
    public const TYPE_CONFIRMATION = 'confirmation';

    public function __construct(
        private NewsletterRepository $depotNewsletter,
        private string $adresseExpediteur,
        private string $nomExpediteur,
        private string $urlPublique,
        private string $modeLivraison = 'direct',
        private int $tailleLot = 20
    ) {
        $this->adresseExpediteur = $this->normaliserAdresseExpediteur($this->adresseExpediteur);
        $this->nomExpediteur = $this->nettoyerEntete($this->nomExpediteur !== '' ? $this->nomExpediteur : "Cavaliers d'Herouville");
        $this->urlPublique = rtrim($this->urlPublique, '/');
        $this->modeLivraison = in_array($this->modeLivraison, ['direct', 'queue'], true) ? $this->modeLivraison : 'direct';
        $this->tailleLot = max(1, $this->tailleLot);
    }

    public static function depuisEnvironnement(NewsletterRepository $depotNewsletter): self
    {
        $adresseExpediteur = config('mail.from.address');
        $nomExpediteur = config('mail.from.name');
        $urlPublique = env('NEWSLETTER_PUBLIC_BASE_URL', config('app.url', '/'));

        return new self(
            $depotNewsletter,
            is_string($adresseExpediteur) && $adresseExpediteur !== '' ? $adresseExpediteur : 'noreply@cavaliers-herouville.fr',
            is_string($nomExpediteur) && $nomExpediteur !== '' ? $nomExpediteur : "Cavaliers d'Herouville",
            is_string($urlPublique) && $urlPublique !== '' ? $urlPublique : '/',
            (string) config('services.o2switch.newsletter_delivery_mode', 'direct'),
            (int) config('services.o2switch.newsletter_batch_size', 20)
        );
    }

    public function envoyerConfirmation(array $abonnement): void
    {
        $courriel = (string) ($abonnement['courriel'] ?? '');
        $identifiant = (string) ($abonnement['identifiant_abonnement'] ?? '');
        $jeton = (string) ($abonnement['jeton_desabonnement'] ?? '');

        if ($courriel === '' || $identifiant === '' || $jeton === '') {
            return;
        }

        $urlAccueil = $this->construireUrl('/');
        $urlDesabonnement = $this->construireUrl(
            route('newsletter.unsubscribe', ['jeton' => $jeton], false)
        );
        $resultat = $this->envoyerMailable(
            $courriel,
            new NewsletterConfirmationMail($urlAccueil, $urlDesabonnement)
        );

        $this->depotNewsletter->enregistrerEnvoi(
            $identifiant,
            self::TYPE_CONFIRMATION,
            'Confirmation abonnement newsletter',
            $urlAccueil,
            'Abonnement newsletter confirme',
            $resultat['succes'] ? 'envoye' : 'echec',
            $resultat['erreur']
        );
    }

    public function notifierArticlePublie(array $article): void
    {
        $titre = trim((string) ($article['titre'] ?? 'Nouvel article'));
        $this->notifierTous(
            self::TYPE_ARTICLE,
            'Nouvel article: ' . $titre,
            $titre,
            $this->construireUrl('/articles'),
            "Une nouvelle actualite vient d'etre publiee sur le site du club.\n\nArticle: {$titre}\n\n"
        );
    }

    public function notifierHorairesMisAJour(string $libelleSaison): void
    {
        $titre = trim($libelleSaison) !== '' ? trim($libelleSaison) : 'Horaires du club';
        $this->notifierTous(
            self::TYPE_COURS,
            'Horaires et cours mis a jour',
            $titre,
            $this->construireUrl('/#emploi-du-temps-complet'),
            "Les horaires, cours ou informations de planning du club viennent d'etre mis a jour.\n\n{$titre}\n\n"
        );
    }

    public function notifierNouvelObjetBoutique(string $titreProduit): void
    {
        $titre = trim($titreProduit) !== '' ? trim($titreProduit) : 'Nouvel objet boutique';
        $this->notifierTous(
            self::TYPE_BOUTIQUE,
            'Nouvel objet dans la boutique',
            $titre,
            $this->construireUrl('/boutique'),
            "Un nouvel objet ou une nouvelle information boutique vient d'etre publiee.\n\n{$titre}\n\n"
        );
    }

    public function notifierNouvelEvenement(string $titreEvenement, string $urlEvenement = '/activites'): void
    {
        $titre = trim($titreEvenement) !== '' ? trim($titreEvenement) : 'Nouvel evenement';
        $this->notifierTous(
            self::TYPE_EVENEMENT,
            'Nouvel evenement special du club',
            $titre,
            $this->construireUrl($urlEvenement),
            "Un nouvel evenement special vient d'etre ajoute par le club.\n\n{$titre}\n\n"
        );
    }

    private function notifierTous(string $typeEvenement, string $sujet, string $titreEvenement, string $urlEvenement, string $message): void
    {
        if ($this->utiliseFileAttente()) {
            $this->mettreEnFileTous($typeEvenement, $sujet, $titreEvenement, $urlEvenement, $message);

            return;
        }

        foreach ($this->depotNewsletter->listerActifs() as $abonnement) {
            $this->notifierUnAbonne($abonnement, $typeEvenement, $sujet, $titreEvenement, $urlEvenement, $message);
        }
    }

    private function notifierUnAbonne(
        array $abonnement,
        string $typeEvenement,
        string $sujet,
        string $titreEvenement,
        string $urlEvenement,
        string $message
    ): void {
        $courriel = (string) ($abonnement['courriel'] ?? '');
        $identifiant = (string) ($abonnement['identifiant_abonnement'] ?? '');
        $jeton = (string) ($abonnement['jeton_desabonnement'] ?? '');

        if ($courriel === '' || $identifiant === '') {
            return;
        }

        $lienDesabonnement = $this->construireUrl(
            route('newsletter.unsubscribe', ['jeton' => $jeton], false)
        );
        $resultat = $this->envoyerMailable(
            $courriel,
            new NewsletterActualiteMail(
                $sujet,
                $titreEvenement,
                trim($message),
                $urlEvenement,
                $lienDesabonnement
            )
        );

        $this->depotNewsletter->enregistrerEnvoi(
            $identifiant,
            $typeEvenement,
            $titreEvenement,
            $urlEvenement,
            $sujet,
            $resultat['succes'] ? 'envoye' : 'echec',
            $resultat['erreur']
        );
    }

    /**
     * @return array{succes: bool, erreur: string}
     */
    private function envoyerMailable(string $destinataire, Mailable $mailable): array
    {
        if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            return [
                'succes' => false,
                'erreur' => 'Adresse email destinataire invalide.',
            ];
        }

        try {
            Mail::to($destinataire)->send($mailable);

            return [
                'succes' => true,
                'erreur' => '',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'succes' => false,
                'erreur' => $this->limiter(
                    $exception->getMessage() !== '' ? $exception->getMessage() : "L'envoi SMTP a echoue.",
                    1000
                ),
            ];
        }
    }

    private function construireUrl(string $chemin): string
    {
        if ($this->urlPublique === '' || $this->urlPublique === '/') {
            return $chemin;
        }

        return $this->urlPublique . '/' . ltrim($chemin, '/');
    }

    private function normaliserAdresseExpediteur(string $adresse): string
    {
        $adresse = $this->nettoyerEntete($adresse);

        return filter_var($adresse, FILTER_VALIDATE_EMAIL) ? $adresse : 'noreply@cavaliers-herouville.fr';
    }

    private function nettoyerEntete(string $valeur): string
    {
        return trim(str_replace(["\r", "\n"], '', $valeur));
    }

    private function limiter(string $valeur, int $limite): string
    {
        return function_exists('mb_substr') ? mb_substr($valeur, 0, $limite) : substr($valeur, 0, $limite);
    }

    private function utiliseFileAttente(): bool
    {
        try {
            return $this->modeLivraison === 'queue'
                && Schema::hasTable('newsletter_campaigns')
                && Schema::hasTable('newsletter_queue');
        } catch (Throwable) {
            return false;
        }
    }

    private function mettreEnFileTous(
        string $typeEvenement,
        string $sujet,
        string $titreEvenement,
        string $urlEvenement,
        string $message
    ): void {
        $abonnes = $this->depotNewsletter->listerActifs();

        if ($abonnes === []) {
            return;
        }

        $campagneId = 'campaign_' . bin2hex(random_bytes(8));
        $maintenant = date('Y-m-d H:i:s');

        DB::table('newsletter_campaigns')->insert([
            'campaign_id' => $campagneId,
            'event_type' => $typeEvenement,
            'subject' => $sujet,
            'title' => $titreEvenement,
            'event_url' => $urlEvenement,
            'message_text' => trim($message),
            'sender_email' => $this->adresseExpediteur,
            'sender_name' => $this->nomExpediteur,
            'status' => 'queued',
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ]);

        $lignes = [];

        foreach ($abonnes as $abonnement) {
            $courriel = (string) ($abonnement['courriel'] ?? '');
            $identifiant = (string) ($abonnement['identifiant_abonnement'] ?? '');
            $jeton = (string) ($abonnement['jeton_desabonnement'] ?? '');

            if ($courriel === '' || $identifiant === '' || ! filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $lignes[] = [
                'queue_id' => 'queue_' . bin2hex(random_bytes(10)),
                'campaign_id' => $campagneId,
                'newsletter_abonnement_id' => $identifiant,
                'recipient_email' => $courriel,
                'unsubscribe_token' => $jeton,
                'template_type' => 'actualite',
                'event_type' => $typeEvenement,
                'subject' => $sujet,
                'title' => $titreEvenement,
                'message_text' => trim($message),
                'event_url' => $urlEvenement,
                'status' => 'pending',
                'attempt_count' => 0,
                'available_at' => $maintenant,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ];
        }

        foreach (array_chunk($lignes, max(1, $this->tailleLot)) as $bloc) {
            DB::table('newsletter_queue')->insert($bloc);
        }
    }
}
