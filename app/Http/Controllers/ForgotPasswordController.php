<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ResetPasswordLinkMail;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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
            'courriel' => ['required', 'email'],
        ]);

        $courriel = mb_strtolower(trim((string) $validated['courriel']));
        $utilisateur = $userRepository->trouverModeleParCourriel($courriel);

        if ($utilisateur !== null) {
            $token = Password::broker()->createToken($utilisateur);
            $urlReinitialisation = route('password.reset', [
                'token' => $token,
                'courriel' => $courriel,
            ]);

            try {
                Mail::to($courriel)->send(new ResetPasswordLinkMail(
                    $urlReinitialisation,
                    trim((string) $utilisateur->prenom) !== '' ? (string) $utilisateur->prenom : $courriel
                ));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Si un compte correspond à cette adresse, un lien de réinitialisation a été envoyé.');
    }
}
