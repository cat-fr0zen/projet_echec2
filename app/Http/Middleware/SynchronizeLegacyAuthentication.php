<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class SynchronizeLegacyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $identifiantLegacy = trim((string) $session->get('identifiant_utilisateur', ''));

        if ($identifiantLegacy === '' && isset($_SESSION['identifiant_utilisateur']) && is_string($_SESSION['identifiant_utilisateur'])) {
            $identifiantLegacy = trim($_SESSION['identifiant_utilisateur']);
        }

        if (Auth::guard('web')->guest() && $identifiantLegacy !== '') {
            $utilisateur = Auth::guard('web')->loginUsingId($identifiantLegacy);

            if ($utilisateur === false) {
                $session->forget('identifiant_utilisateur');
                unset($_SESSION['identifiant_utilisateur']);
            }
        }

        $identifiantAuthentifie = Auth::guard('web')->id();

        if ($identifiantAuthentifie !== null && trim((string) $identifiantAuthentifie) !== '') {
            if ((string) $identifiantAuthentifie !== $identifiantLegacy) {
                $session->put('identifiant_utilisateur', (string) $identifiantAuthentifie);
            }

            $_SESSION['identifiant_utilisateur'] = (string) $identifiantAuthentifie;
        } elseif ($identifiantLegacy !== '') {
            $session->forget('identifiant_utilisateur');
            unset($_SESSION['identifiant_utilisateur']);
        }

        return $next($request);
    }
}
