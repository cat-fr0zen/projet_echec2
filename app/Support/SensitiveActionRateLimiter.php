<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

final class SensitiveActionRateLimiter
{
    /**
     * @return array{action: string, retry_after: int, message: string}|null
     */
    public function verifierBlocage(string $action, array $donnees): ?array
    {
        $politique = $this->resoudrePolitique($action);

        if ($politique === null) {
            return null;
        }

        $cle = $this->construireCle($politique['action'], $donnees);

        if (! RateLimiter::tooManyAttempts($cle, $politique['max_attempts'])) {
            return null;
        }

        $retryAfter = RateLimiter::availableIn($cle);
        $minutes = max(1, (int) ceil($retryAfter / 60));

        return [
            'action' => $politique['action'],
            'retry_after' => $retryAfter,
            'message' => str_replace(':minutes', (string) $minutes, $politique['message']),
        ];
    }

    public function enregistrerTentative(string $action, array $donnees): void
    {
        $politique = $this->resoudrePolitique($action);

        if ($politique === null) {
            return;
        }

        RateLimiter::hit(
            $this->construireCle($politique['action'], $donnees),
            $politique['decay_seconds']
        );
    }

    public function reinitialiser(string $action, array $donnees): void
    {
        $politique = $this->resoudrePolitique($action);

        if ($politique === null || ! $politique['clear_on_success']) {
            return;
        }

        RateLimiter::clear($this->construireCle($politique['action'], $donnees));
    }

    public function bloquerSiNecessaire(string $action, array $donnees, string $pageRedirection): void
    {
        $blocage = $this->verifierBlocage($action, $donnees);

        if ($blocage === null) {
            return;
        }

        $actionNormalisee = (string) ($blocage['action'] ?? '');
        $message = (string) ($blocage['message'] ?? 'Trop de tentatives. Merci de reessayer plus tard.');

        if ($actionNormalisee === 'connexion') {
            $identifiantConnexion = trim((string) ($donnees['identifiant_connexion'] ?? $donnees['courriel'] ?? $donnees['email'] ?? ''));
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'connexion',
                'erreurs' => [$message],
                'anciennes_valeurs' => [
                    'identifiant_connexion' => $identifiantConnexion,
                    'courriel' => $identifiantConnexion,
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        if ($actionNormalisee === 'inscription') {
            memoriser_etat_formulaire([
                'ouverte' => true,
                'onglet' => 'inscription',
                'erreurs' => [$message],
                'anciennes_valeurs' => [
                    'nom' => trim((string) ($donnees['nom'] ?? '')),
                    'prenom' => trim((string) ($donnees['prenom'] ?? '')),
                    'date_naissance' => trim((string) ($donnees['date_naissance'] ?? '')),
                    'courriel' => trim((string) ($donnees['courriel'] ?? $donnees['email'] ?? '')),
                    'numero_licence' => trim((string) ($donnees['numero_licence'] ?? '')),
                    'description_profil' => trim((string) ($donnees['description_profil'] ?? '')),
                    'pseudo_chess' => trim((string) ($donnees['pseudo_chess'] ?? '')),
                ],
            ]);
            rediriger_vers(url_route($pageRedirection));
        }

        if ($actionNormalisee === 'newsletter_subscribe') {
            ajouter_message_flash('error', $message);
            rediriger_vers(url_route($pageRedirection).'#footer-newsletter-title');
        }

        ajouter_message_flash('error', $message);
        rediriger_vers(url_route($pageRedirection));
    }

    /**
     * @return array{action: string, max_attempts: int, decay_seconds: int, message: string, clear_on_success: bool}|null
     */
    private function resoudrePolitique(string $action): ?array
    {
        return match ($action) {
            'connexion', 'login' => [
                'action' => 'connexion',
                'max_attempts' => 5,
                'decay_seconds' => 900,
                'message' => 'Trop de tentatives de connexion. Merci de reessayer dans :minutes minute(s).',
                'clear_on_success' => true,
            ],
            'inscription', 'register' => [
                'action' => 'inscription',
                'max_attempts' => 3,
                'decay_seconds' => 3600,
                'message' => 'Trop de tentatives de creation de compte. Merci de reessayer dans :minutes minute(s).',
                'clear_on_success' => true,
            ],
            'inscription_newsletter', 'newsletter_subscribe' => [
                'action' => 'newsletter_subscribe',
                'max_attempts' => 10,
                'decay_seconds' => 3600,
                'message' => 'Trop de demandes newsletter depuis cette source. Merci de reessayer dans :minutes minute(s).',
                'clear_on_success' => false,
            ],
            'creer_article', 'create_article' => [
                'action' => 'create_article',
                'max_attempts' => 6,
                'decay_seconds' => 1800,
                'message' => 'Trop de soumissions d articles. Merci de reessayer dans :minutes minute(s).',
                'clear_on_success' => false,
            ],
            'soumettre_media', 'submit_media' => [
                'action' => 'submit_media',
                'max_attempts' => 6,
                'decay_seconds' => 1800,
                'message' => 'Trop de televersements. Merci de reessayer dans :minutes minute(s).',
                'clear_on_success' => false,
            ],
            default => null,
        };
    }

    private function construireCle(string $action, array $donnees): string
    {
        $ip = trim((string) ($donnees['__ip'] ?? request()->ip() ?? 'inconnue'));

        return match ($action) {
            'connexion' => sprintf(
                'legacy-action|connexion|%s|%s',
                $ip,
                $this->normaliserIdentifiant(trim((string) ($donnees['identifiant_connexion'] ?? $donnees['courriel'] ?? $donnees['email'] ?? 'anonyme')))
            ),
            'inscription' => sprintf(
                'legacy-action|inscription|%s|%s',
                $ip,
                $this->normaliserIdentifiant(trim((string) ($donnees['courriel'] ?? $donnees['email'] ?? 'anonyme')))
            ),
            'newsletter_subscribe' => sprintf(
                'legacy-action|newsletter|%s|%s',
                $ip,
                $this->normaliserIdentifiant(trim((string) ($donnees['newsletter_email'] ?? 'anonyme')))
            ),
            'create_article', 'submit_media' => sprintf(
                'legacy-action|%s|%s|%s',
                $action,
                $ip,
                $this->normaliserIdentifiant((string) (session('identifiant_utilisateur') ?? 'visiteur'))
            ),
            default => sprintf('legacy-action|%s|%s', $action, $ip),
        };
    }

    private function normaliserIdentifiant(string $valeur): string
    {
        if ($valeur === '') {
            return 'anonyme';
        }

        return function_exists('mb_strtolower')
            ? mb_strtolower($valeur)
            : strtolower($valeur);
    }
}
