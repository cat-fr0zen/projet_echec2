<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class UserRepository
{
    public function trouverParIdentifiant(?string $identifiant): ?array
    {
        if ($identifiant === null || trim($identifiant) === '') {
            return null;
        }

        $row = DB::table('compte_membre')
            ->where('identifiant', trim($identifiant))
            ->first();

        return $row !== null ? $this->normaliserUtilisateur((array) $row) : null;
    }

    public function listerTous(): array
    {
        return array_map(
            fn (object $row): array => $this->normaliserUtilisateur((array) $row),
            DB::table('compte_membre')->orderByDesc('cree_le')->get()->all()
        );
    }

    public function trouverParCourriel(string $courriel): ?array
    {
        $email = mb_strtolower(trim($courriel));
        $row = DB::table('compte_membre')
            ->where('courriel_normalise', $email)
            ->first();

        return $row !== null ? $this->normaliserUtilisateur((array) $row) : null;
    }

    public function trouverParNumeroLicence(string $numeroLicence): ?array
    {
        $license = $this->normaliserNumeroLicenceFederale($numeroLicence);

        if ($license === '') {
            return null;
        }

        $row = DB::table('compte_membre')
            ->whereRaw('UPPER(numero_licence_federale) = ?', [$license])
            ->first();

        return $row !== null ? $this->normaliserUtilisateur((array) $row) : null;
    }

    public function trouverParIdentifiantConnexion(string $identifiantConnexion): ?array
    {
        $identifier = trim($identifiantConnexion);

        if ($identifier === '') {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->trouverParCourriel($identifier);
        }

        return $this->trouverParNumeroLicence($identifier);
    }

    public function creer(array $donnees): array
    {
        $isFirstAccount = DB::table('compte_membre')->count() === 0;
        $identifiant = 'utilisateur_' . bin2hex(random_bytes(8));
        $license = $this->normaliserNumeroLicenceFederale($donnees['numero_licence'] ?? '');
        $email = mb_strtolower(trim((string) ($donnees['courriel'] ?? '')));

        DB::table('compte_membre')->insert([
            'identifiant' => $identifiant,
            'nom' => (string) ($donnees['nom'] ?? ''),
            'prenom' => (string) ($donnees['prenom'] ?? ''),
            'date_naissance' => (string) ($donnees['date_naissance'] ?? ''),
            'courriel' => $email,
            'courriel_normalise' => $email,
            'numero_licence_federale' => $license !== '' ? $license : null,
            'mot_de_passe_hache' => password_hash((string) ($donnees['mot_de_passe'] ?? ''), PASSWORD_DEFAULT),
            'description_profil' => (string) ($donnees['description_profil'] ?? ''),
            'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
            'code_role' => $isFirstAccount ? User::ROLE_ADMIN : User::ROLE_CONNECTE,
            'code_statut_compte' => User::STATUT_COMPTE_ACTIF,
            'code_statut_adhesion' => $isFirstAccount ? User::STATUT_ADHESION_ACTIVE : User::STATUT_ADHESION_AUCUNE,
            'cree_le' => date('Y-m-d H:i:s'),
        ]);

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    public function mettreAJour(string $identifiant, array $donnees): ?array
    {
        $license = $this->normaliserNumeroLicenceFederale($donnees['numero_licence'] ?? '');

        $updated = DB::table('compte_membre')
            ->where('identifiant', $identifiant)
            ->update([
                'nom' => (string) ($donnees['nom'] ?? ''),
                'prenom' => (string) ($donnees['prenom'] ?? ''),
                'date_naissance' => (string) ($donnees['date_naissance'] ?? ''),
                'numero_licence_federale' => $license !== '' ? $license : null,
                'description_profil' => (string) ($donnees['description_profil'] ?? ''),
                'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]);

        return $updated > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function mettreAJourAcces(string $identifiant, string $role, string $statutCompte, string $statutAdhesion): ?array
    {
        if (!in_array($role, [User::ROLE_CONNECTE, User::ROLE_ADHERENT, User::ROLE_ADMIN], true)) {
            return null;
        }

        if (!in_array($statutCompte, [User::STATUT_COMPTE_ACTIF, User::STATUT_COMPTE_SUSPENDU], true)) {
            return null;
        }

        if (!in_array($statutAdhesion, [User::STATUT_ADHESION_AUCUNE, User::STATUT_ADHESION_ACTIVE], true)) {
            return null;
        }

        $user = $this->trouverParIdentifiant($identifiant);
        if ($user === null) {
            return null;
        }

        if ($user['role'] === User::ROLE_ADMIN && $role !== User::ROLE_ADMIN && $this->compterAdministrateurs() <= 1) {
            return null;
        }

        DB::table('compte_membre')
            ->where('identifiant', $identifiant)
            ->update([
                'code_role' => $role,
                'code_statut_compte' => $statutCompte,
                'code_statut_adhesion' => $statutAdhesion,
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]);

        return $this->trouverParIdentifiant($identifiant);
    }

    public function normaliserNumeroLicenceFederale(mixed $valeur): string
    {
        $numero = mb_strtoupper(trim((string) $valeur));

        return preg_replace('/\s+/', '', $numero) ?? '';
    }

    private function normaliserUtilisateur(array $row): array
    {
        return [
            'identifiant' => (string) ($row['identifiant'] ?? ''),
            'nom' => (string) ($row['nom'] ?? ''),
            'prenom' => (string) ($row['prenom'] ?? ''),
            'date_naissance' => (string) ($row['date_naissance'] ?? ''),
            'courriel' => mb_strtolower((string) ($row['courriel'] ?? '')),
            'numero_licence' => $this->normaliserNumeroLicenceFederale($row['numero_licence_federale'] ?? ''),
            'mot_de_passe_hache' => (string) ($row['mot_de_passe_hache'] ?? ''),
            'description_profil' => (string) ($row['description_profil'] ?? ''),
            'pseudo_chess' => $this->normaliserPseudoChess($row['pseudo_chess'] ?? ''),
            'role' => (string) ($row['code_role'] ?? User::ROLE_CONNECTE),
            'statut_compte' => (string) ($row['code_statut_compte'] ?? User::STATUT_COMPTE_ACTIF),
            'statut_adhesion' => (string) ($row['code_statut_adhesion'] ?? User::STATUT_ADHESION_AUCUNE),
            'cree_le' => $this->formaterDateIso($row['cree_le'] ?? null),
            'mis_a_jour_le' => $this->formaterDateIso($row['mis_a_jour_le'] ?? null),
        ];
    }

    private function normaliserPseudoChess(mixed $valeur): string
    {
        return mb_strtolower(trim((string) $valeur));
    }

    private function compterAdministrateurs(): int
    {
        return (int) DB::table('compte_membre')
            ->where('code_role', User::ROLE_ADMIN)
            ->count();
    }

    private function formaterDateIso(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable((string) $value))->format('c');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
