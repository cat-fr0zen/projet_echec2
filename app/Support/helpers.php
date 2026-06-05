<?php

declare(strict_types=1);

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

if (! function_exists('normaliser_texte_utf8')) {
    function normaliser_texte_utf8(?string $valeur): string
    {
        $texte = (string) $valeur;

        if ($texte === '') {
            return '';
        }

        if (! preg_match('//u', $texte) && function_exists('mb_convert_encoding')) {
            $texte = mb_convert_encoding($texte, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
        }

        static $corrections = null;

        if ($corrections === null) {
            $corrections = [
                hex2bin('C383C2A0') => "\u{00E0}",
                hex2bin('C383C2A2') => "\u{00E2}",
                hex2bin('C383C2A4') => "\u{00E4}",
                hex2bin('C383C2A7') => "\u{00E7}",
                hex2bin('C383C2A8') => "\u{00E8}",
                hex2bin('C383C2A9') => "\u{00E9}",
                hex2bin('C383C2AA') => "\u{00EA}",
                hex2bin('C383C2AB') => "\u{00EB}",
                hex2bin('C383C2AE') => "\u{00EE}",
                hex2bin('C383C2AF') => "\u{00EF}",
                hex2bin('C383C2B4') => "\u{00F4}",
                hex2bin('C383C2B6') => "\u{00F6}",
                hex2bin('C383C2B9') => "\u{00F9}",
                hex2bin('C383C2BB') => "\u{00FB}",
                hex2bin('C383C2BC') => "\u{00FC}",
                hex2bin('C383E282AC') => "\u{00C0}",
                hex2bin('C383E280A1') => "\u{00C7}",
                hex2bin('C383CB86') => "\u{00C8}",
                hex2bin('C383E280B0') => "\u{00C9}",
                hex2bin('C385E28099') => "\u{0152}",
                hex2bin('C385E2809C') => "\u{0153}",
                hex2bin('C3A2E282ACE284A2') => "\u{2019}",
                hex2bin('C3A2E282ACC593') => "\u{201C}",
                hex2bin('C3A2E282ACC29D') => "\u{201D}",
                hex2bin('C3A2E282ACE28093') => "\u{2013}",
                hex2bin('C3A2E282ACE28094') => "\u{2014}",
                hex2bin('C3A2E282ACC2A6') => "\u{2026}",
                hex2bin('C382C2AB') => "\u{00AB}",
                hex2bin('C382C2BB') => "\u{00BB}",
                hex2bin('C382C2B0') => "\u{00B0}",
                hex2bin('C382C2B7') => "\u{00B7}",
                hex2bin('C382') => '',
            ];
        }

        return strtr($texte, $corrections);
    }
}

if (! function_exists('normaliser_structure_utf8')) {
    function normaliser_structure_utf8(mixed $valeur): mixed
    {
        if (is_array($valeur)) {
            $resultat = [];

            foreach ($valeur as $cle => $element) {
                $resultat[$cle] = normaliser_structure_utf8($element);
            }

            return $resultat;
        }

        return is_string($valeur) ? normaliser_texte_utf8($valeur) : $valeur;
    }
}

if (! function_exists('url_route')) {
    function url_route(string $segment, array $parametres = []): string
    {
        $segmentNormalise = trim($segment, '/');
        $chemin = $segmentNormalise === '' || $segmentNormalise === 'accueil'
            ? '/'
            : '/'.rawurlencode($segmentNormalise);

        if ($parametres === []) {
            return $chemin;
        }

        return $chemin.'?'.http_build_query($parametres);
    }
}

if (! function_exists('url_ressource')) {
    function url_ressource(string $chemin): string
    {
        $cheminNormalise = ltrim(str_replace('\\', '/', $chemin), '/');

        if (str_starts_with($cheminNormalise, 'assets/styles/')) {
            return asset($cheminNormalise);
        }

        if (str_starts_with($cheminNormalise, 'ressources/styles/')) {
            return asset('assets/styles/'.basename($cheminNormalise));
        }

        if (str_starts_with($cheminNormalise, 'assets/scripts/')) {
            return asset($cheminNormalise);
        }

        if (str_starts_with($cheminNormalise, 'ressources/scripts/')) {
            return asset('assets/scripts/'.basename($cheminNormalise));
        }

        if (str_starts_with($cheminNormalise, 'assets/media/')) {
            return asset($cheminNormalise);
        }

        if (str_starts_with($cheminNormalise, 'ressources/media/')) {
            $suffixe = substr($cheminNormalise, strlen('ressources/media/'));

            return asset('assets/media/'.$suffixe);
        }

        return asset($cheminNormalise);
    }
}

if (! function_exists('theme_courant')) {
    function theme_courant(): string
    {
        $theme = request()->cookie('site_theme', 'light');

        return $theme === 'dark' ? 'dark' : 'light';
    }
}

if (! function_exists('jeton_csrf')) {
    function jeton_csrf(): string
    {
        return csrf_token();
    }
}

if (! function_exists('verifier_jeton_csrf')) {
    function verifier_jeton_csrf(mixed $jeton): bool
    {
        return is_string($jeton) && hash_equals(csrf_token(), $jeton);
    }
}

if (! function_exists('ajouter_message_flash')) {
    function ajouter_message_flash(string $type, string $message): void
    {
        $messages = session('messages_flash', []);
        $messages[] = [
            'type' => $type,
            'message' => normaliser_texte_utf8($message),
        ];
        session(['messages_flash' => $messages]);
    }
}

if (! function_exists('recuperer_messages_flash')) {
    function recuperer_messages_flash(): array
    {
        $messages = session()->pull('messages_flash', []);

        return is_array($messages) ? $messages : [];
    }
}

if (! function_exists('memoriser_etat_formulaire')) {
    function memoriser_etat_formulaire(array $etat): void
    {
        session(['etat_formulaire' => $etat]);
    }
}

if (! function_exists('recuperer_etat_formulaire')) {
    function recuperer_etat_formulaire(): array
    {
        $etat = session()->pull('etat_formulaire', []);

        return is_array($etat) ? $etat : [];
    }
}

if (! function_exists('identifiant_utilisateur_courant')) {
    function identifiant_utilisateur_courant(): ?string
    {
        $identifiantAuthentifie = Auth::guard('web')->id();

        if ($identifiantAuthentifie !== null && trim((string) $identifiantAuthentifie) !== '') {
            return (string) $identifiantAuthentifie;
        }

        $identifiantLegacy = session('identifiant_utilisateur');

        if (is_string($identifiantLegacy) && trim($identifiantLegacy) !== '') {
            return trim($identifiantLegacy);
        }

        if (isset($_SESSION['identifiant_utilisateur']) && is_string($_SESSION['identifiant_utilisateur']) && trim($_SESSION['identifiant_utilisateur']) !== '') {
            return trim($_SESSION['identifiant_utilisateur']);
        }

        return null;
    }
}

if (! function_exists('connecter_utilisateur_courant')) {
    function connecter_utilisateur_courant(string $identifiantUtilisateur): void
    {
        $identifiantNormalise = trim($identifiantUtilisateur);

        if ($identifiantNormalise === '') {
            return;
        }

        try {
            Auth::guard('web')->loginUsingId($identifiantNormalise);
        } catch (Throwable) {
            // Garde un fonctionnement legacy si la couche auth Laravel n'est pas encore disponible.
        }

        if (app()->bound('session.store')) {
            $session = session();
            $session->migrate(true);
            $session->put('identifiant_utilisateur', $identifiantNormalise);
            $_SESSION['identifiant_utilisateur'] = $identifiantNormalise;

            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        session_regenerate_id(true);
        $_SESSION['identifiant_utilisateur'] = $identifiantNormalise;
    }
}

if (! function_exists('deconnecter_utilisateur_courant')) {
    function deconnecter_utilisateur_courant(): void
    {
        Auth::guard('web')->logout();

        if (app()->bound('session.store')) {
            $session = session();
            $session->forget('identifiant_utilisateur');
            $session->migrate(true);
            unset($_SESSION['identifiant_utilisateur']);

            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['identifiant_utilisateur']);
            session_regenerate_id(true);
        }
    }
}

if (! function_exists('rediriger_vers')) {
    function rediriger_vers(string $url): never
    {
        /** @var RedirectResponse $response */
        $response = redirect($url);

        throw new HttpResponseException($response);
    }
}

if (! function_exists('repondre_json_et_terminer')) {
    function repondre_json_et_terminer(array $payload, int $statusCode = 200): never
    {
        throw new HttpResponseException(Response::json($payload, $statusCode));
    }
}
