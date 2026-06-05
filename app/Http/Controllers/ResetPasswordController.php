<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
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
            'courriel' => trim((string) $request->query('courriel', '')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'courriel' => ['required', 'email'],
            'mot_de_passe' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            [
                'courriel' => mb_strtolower(trim((string) $validated['courriel'])),
                'password' => (string) $validated['mot_de_passe'],
                'password_confirmation' => (string) $request->input('mot_de_passe_confirmation'),
                'token' => (string) $validated['token'],
            ],
            static function (User $user, string $password): void {
                $user->forceFill([
                    'mot_de_passe_hache' => Hash::make($password),
                    'mis_a_jour_le' => date('Y-m-d H:i:s'),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'courriel' => ['Le lien de réinitialisation est invalide ou expiré.'],
            ]);
        }

        ajouter_message_flash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.');

        return redirect(url_route('accueil'));
    }
}
