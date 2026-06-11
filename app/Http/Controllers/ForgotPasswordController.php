<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ResetPasswordLinkMail;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, UserRepository $userRepository): RedirectResponse
    {
        $validated = $request->validate([
            'identifiant_reinitialisation' => ['required', 'string', 'max:254'],
        ]);

        $identifiant = trim((string) $validated['identifiant_reinitialisation']);
        $utilisateur = null;
        $urlReinitialisation = '';
        $messageCourrielPartage = "La reinitialisation automatique n'est pas disponible pour un email partage. Contactez l'administrateur du club.";

        if (filter_var($identifiant, FILTER_VALIDATE_EMAIL)) {
            if ($userRepository->compterParCourriel($identifiant) > 1) {
                throw ValidationException::withMessages([
                    'identifiant_reinitialisation' => $messageCourrielPartage,
                ]);
            }

            $courriel = mb_strtolower($identifiant);
            $utilisateur = $userRepository->trouverModeleParCourriel($courriel);

            if ($utilisateur !== null) {
                $token = Password::broker()->createToken($utilisateur);
                $urlReinitialisation = route('password.reset', [
                    'token' => $token,
                    'identifiant_reinitialisation' => $courriel,
                ]);
            }
        } else {
            $numeroLicence = $userRepository->normaliserNumeroLicenceFederale($identifiant);
            $utilisateur = $userRepository->trouverModeleParNumeroLicence($numeroLicence);

            if (
                $utilisateur !== null
                && $userRepository->compterParCourriel((string) $utilisateur->courriel) > 1
            ) {
                throw ValidationException::withMessages([
                    'identifiant_reinitialisation' => $messageCourrielPartage,
                ]);
            }

            if ($utilisateur !== null) {
                $token = Password::broker()->createToken($utilisateur);
                $urlReinitialisation = route('password.reset', [
                    'token' => $token,
                    'identifiant_reinitialisation' => $numeroLicence,
                ]);
            }
        }

        if ($utilisateur !== null) {
            try {
                Mail::to((string) $utilisateur->courriel)->send(new ResetPasswordLinkMail(
                    $urlReinitialisation,
                    trim((string) $utilisateur->prenom) !== '' ? (string) $utilisateur->prenom : (string) $utilisateur->courriel
                ));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Si un compte correspond a cet identifiant, un lien de reinitialisation a ete envoye.');
    }
}
