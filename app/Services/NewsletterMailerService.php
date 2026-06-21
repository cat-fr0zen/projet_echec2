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
use Illuminate\Support\Facades\Mail;
use Throwable;

final class NewsletterMailerService
{
    public const TYPE_ARTICLE = 'article';
    public const TYPE_COURS = 'cours';
    public const TYPE_BOUTIQUE = 'boutique';
    public const TYPE_CONFIRMATION = 'confirmation';

    public function __construct(
        private NewsletterRepository $depotNewsletter,
        private string $adresseExpediteur,
        private string $nomExpediteur,
        private string $urlPublique
    ) {
        $this->adresseExpediteur = $this->normaliserAdresseExpediteur($this->adresseExpediteur);
        $this->nomExpediteur = $this->nettoyerEntete($this->nomExpediteur !== '' ? $this->nomExpediteur : "Cavaliers d'Herouville");
        $this->urlPublique = rtrim($this->urlPublique, '/');
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
            is_string($urlPublique) && $urlPublique !== '' ? $urlPublique : '/'
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

    private function notifierTous(string $typeEvenement, string $sujet, string $titreEvenement, string $urlEvenement, string $message): void
    {
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
}
