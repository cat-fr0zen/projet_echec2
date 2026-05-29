<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NewsletterRepository;

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
        $adresseExpediteur = getenv('MAIL_FROM_ADDRESS');
        $nomExpediteur = getenv('MAIL_FROM_NAME');
        $urlPublique = getenv('NEWSLETTER_PUBLIC_BASE_URL');

        return new self(
            $depotNewsletter,
            is_string($adresseExpediteur) && $adresseExpediteur !== '' ? $adresseExpediteur : 'noreply@cavaliers-herouville.fr',
            is_string($nomExpediteur) && $nomExpediteur !== '' ? $nomExpediteur : "Cavaliers d'Herouville",
            is_string($urlPublique) && $urlPublique !== '' ? $urlPublique : '/'
        );
    }

    public function envoyerConfirmation(array $abonnement): void
    {
        $this->notifierUnAbonne(
            $abonnement,
            self::TYPE_CONFIRMATION,
            'Abonnement newsletter confirme',
            'Bienvenue dans la newsletter du club',
            $this->construireUrl('/'),
            "Bonjour,\n\nVotre abonnement a la newsletter des Cavaliers d'Herouville est confirme.\n\nVous recevrez les nouvelles actualites publiees par le club: articles, horaires/cours et boutique.\n\n"
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

        $lienDesabonnement = $this->construireUrl('/?newsletter_unsubscribe=' . rawurlencode($jeton));
        $corps = $message
            . "Voir l'actualite: {$urlEvenement}\n\n"
            . "Vous recevez cet email car vous avez demande a suivre les actualites du club.\n"
            . "Pour vous desabonner: {$lienDesabonnement}\n";

        $envoye = $this->envoyerMail($courriel, $sujet, $corps);

        $this->depotNewsletter->enregistrerEnvoi(
            $identifiant,
            $typeEvenement,
            $titreEvenement,
            $urlEvenement,
            $sujet,
            $envoye ? 'envoye' : 'echec',
            $envoye ? '' : 'mail() a refuse l envoi. Verifier SMTP/sendmail dans XAMPP.'
        );
    }

    private function envoyerMail(string $destinataire, string $sujet, string $corps): bool
    {
        if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $sujet = $this->nettoyerEntete($sujet);
        $headers = [
            'From: ' . $this->nomExpediteur . ' <' . $this->adresseExpediteur . '>',
            'Reply-To: ' . $this->adresseExpediteur,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        return @mail($destinataire, $sujet, $corps, implode("\r\n", $headers));
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
}
