<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : ResetPasswordController.
 */

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ResetPasswordController extends Controller
{
    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'identifiant_reinitialisation' => trim((string) $request->query('identifiant_reinitialisation', $request->query('courriel', ''))),
        ]);
    }

    public function update(Request $request, UserRepository $userRepository): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'identifiant_reinitialisation' => ['required', 'string', 'max:254'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $identifiant = trim((string) $validated['identifiant_reinitialisation']);
        $credentials = [
            'password' => (string) $validated['mot_de_passe'],
            'password_confirmation' => (string) $request->input('mot_de_passe_confirmation'),
            'token' => (string) $validated['token'],
        ];
        $messageCourrielPartage = "La reinitialisation automatique n'est pas disponible pour un email partage. Contactez l'administrateur du club.";

        if (filter_var($identifiant, FILTER_VALIDATE_EMAIL)) {
            if ($userRepository->compterParCourriel($identifiant) > 1) {
                throw ValidationException::withMessages([
                    'identifiant_reinitialisation' => [$messageCourrielPartage],
                ]);
            }

            $credentials['courriel'] = mb_strtolower($identifiant);
        } else {
            $numeroLicence = $userRepository->normaliserNumeroLicenceFederale($identifiant);
            $utilisateur = $userRepository->trouverModeleParNumeroLicence($numeroLicence);

            if ($utilisateur === null) {
                throw ValidationException::withMessages([
                    'identifiant_reinitialisation' => ['Le lien de reinitialisation est invalide ou expire.'],
                ]);
            }

            if ($userRepository->compterParCourriel((string) $utilisateur->courriel) > 1) {
                throw ValidationException::withMessages([
                    'identifiant_reinitialisation' => [$messageCourrielPartage],
                ]);
            }

            $credentials['numero_licence_federale'] = $numeroLicence;
        }

        $status = Password::broker()->reset(
            $credentials,
            static function (User $user, string $password): void {
                $user->forceFill([
                    'mot_de_passe_hache' => Hash::make($password),
                    'mis_a_jour_le' => date('Y-m-d H:i:s'),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'identifiant_reinitialisation' => ['Le lien de reinitialisation est invalide ou expire.'],
            ]);
        }

        ajouter_message_flash('success', 'Votre mot de passe a ete reinitialise. Vous pouvez maintenant vous connecter.');

        return redirect(url_route('accueil'));
    }
}
