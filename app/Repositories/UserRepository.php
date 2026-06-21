<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : UserRepository.
 */

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class UserRepository
{
    /**
     * Point d'entree unique pour lire et modifier les comptes membres.
     */
    /**
     * @return array<string, int>
     */
    public function resumerRoles(): array
    {
        return [
            'admin' => (int) DB::table('compte_membre')->where('code_role', User::ROLE_ADMIN)->count(),
            'prof' => (int) DB::table('compte_membre')->where('code_role', User::ROLE_PROF)->count(),
            'adherent' => (int) DB::table('compte_membre')->where('code_role', User::ROLE_ADHERENT)->count(),
            'connecte' => (int) DB::table('compte_membre')->where('code_role', User::ROLE_CONNECTE)->count(),
        ];
    }

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

    public function trouverModeleParIdentifiant(?string $identifiant): ?User
    {
        if ($identifiant === null || trim($identifiant) === '') {
            return null;
        }

        return User::query()->find(trim($identifiant));
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listerParCourriel(string $courriel): array
    {
        $email = mb_strtolower(trim($courriel));

        if ($email === '') {
            return [];
        }

        return array_map(
            fn (object $row): array => $this->normaliserUtilisateur((array) $row),
            DB::table('compte_membre')
                ->where('courriel_normalise', $email)
                ->orderBy('cree_le')
                ->get()
                ->all()
        );
    }

    public function compterParCourriel(string $courriel): int
    {
        $email = mb_strtolower(trim($courriel));

        if ($email === '') {
            return 0;
        }

        return (int) DB::table('compte_membre')
            ->where('courriel_normalise', $email)
            ->count();
    }

    public function trouverModeleParCourriel(string $courriel): ?User
    {
        $email = mb_strtolower(trim($courriel));

        if ($email === '') {
            return null;
        }

        return User::query()
            ->where('courriel_normalise', $email)
            ->first();
    }

    public function trouverModeleParNumeroLicence(string $numeroLicence): ?User
    {
        $license = $this->normaliserNumeroLicenceFederale($numeroLicence);

        if ($license === '') {
            return null;
        }

        return User::query()
            ->whereRaw('UPPER(numero_licence_federale) = ?', [$license])
            ->first();
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
            return $this->compterParCourriel($identifier) === 1
                ? $this->trouverParCourriel($identifier)
                : null;
        }

        return $this->trouverParNumeroLicence($identifier);
    }

    public function trouverModeleParIdentifiantConnexion(string $identifiantConnexion): ?User
    {
        $identifier = trim($identifiantConnexion);

        if ($identifier === '') {
            return null;
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->compterParCourriel($identifier) === 1
                ? $this->trouverModeleParCourriel($identifier)
                : null;
        }

        $numeroLicence = $this->normaliserNumeroLicenceFederale($identifier);

        if ($numeroLicence === '') {
            return null;
        }

        return $this->trouverModeleParNumeroLicence($numeroLicence);
    }

    public function creer(array $donnees): array
    {
        $isFirstAccount = DB::table('compte_membre')->count() === 0;
        $identifiant = 'utilisateur_'.bin2hex(random_bytes(8));
        $license = $this->normaliserNumeroLicenceFederale($donnees['numero_licence'] ?? '');
        $email = mb_strtolower(trim((string) ($donnees['courriel'] ?? '')));

        DB::table('compte_membre')->insert([
            'identifiant' => $identifiant,
            'nom' => (string) ($donnees['nom'] ?? ''),
            'prenom' => (string) ($donnees['prenom'] ?? ''),
            'date_naissance' => $this->normaliserDateNaissance($donnees['date_naissance'] ?? null),
            'courriel' => $email,
            'courriel_normalise' => $email,
            'numero_licence_federale' => $license !== '' ? $license : null,
            'mot_de_passe_hache' => Hash::make((string) ($donnees['mot_de_passe'] ?? '')),
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
                'date_naissance' => $this->normaliserDateNaissance($donnees['date_naissance'] ?? null),
                'numero_licence_federale' => $license !== '' ? $license : null,
                'description_profil' => (string) ($donnees['description_profil'] ?? ''),
                'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]);

        return $updated > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function mettreAJourMotDePasse(string $identifiant, string $motDePasse): bool
    {
        if (trim($identifiant) === '' || trim($motDePasse) === '') {
            return false;
        }

        return DB::table('compte_membre')
            ->where('identifiant', $identifiant)
            ->update([
                'mot_de_passe_hache' => Hash::make($motDePasse),
                'mis_a_jour_le' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    public function mettreAJourAcces(string $identifiant, string $role, string $statutCompte, string $statutAdhesion): ?array
    {
        if (! in_array($role, [User::ROLE_CONNECTE, User::ROLE_ADHERENT, User::ROLE_PROF, User::ROLE_ADMIN], true)) {
            return null;
        }

        if (! in_array($statutCompte, [User::STATUT_COMPTE_ACTIF, User::STATUT_COMPTE_SUSPENDU], true)) {
            return null;
        }

        if (! in_array($statutAdhesion, [User::STATUT_ADHESION_AUCUNE, User::STATUT_ADHESION_ACTIVE], true)) {
            return null;
        }

        $user = $this->trouverParIdentifiant($identifiant);
        if ($user === null) {
            return null;
        }

        if (
            $role === User::ROLE_PROF
            && $user['role'] !== User::ROLE_PROF
            && $this->compterProfesseurs() >= User::MAX_PROFESSEURS
        ) {
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

    public function transfererRoleAdmin(
        string $identifiantAdministrateurSource,
        string $identifiantUtilisateurCible,
        string $roleApresTransfert = User::ROLE_PROF
    ): ?array {
        if ($identifiantAdministrateurSource === '' || $identifiantUtilisateurCible === '') {
            return null;
        }

        if ($identifiantAdministrateurSource === $identifiantUtilisateurCible) {
            return null;
        }

        if (! in_array($roleApresTransfert, [User::ROLE_CONNECTE, User::ROLE_ADHERENT, User::ROLE_PROF], true)) {
            return null;
        }

        $administrateurSource = $this->trouverParIdentifiant($identifiantAdministrateurSource);
        $utilisateurCible = $this->trouverParIdentifiant($identifiantUtilisateurCible);

        if ($administrateurSource === null || $utilisateurCible === null) {
            return null;
        }

        if ($administrateurSource['role'] !== User::ROLE_ADMIN) {
            return null;
        }

        if ($utilisateurCible['statut_compte'] !== User::STATUT_COMPTE_ACTIF) {
            return null;
        }

        if (
            $roleApresTransfert === User::ROLE_PROF
            && $administrateurSource['role'] !== User::ROLE_PROF
            && $this->compterProfesseurs() >= User::MAX_PROFESSEURS
        ) {
            return null;
        }

        DB::transaction(function () use ($identifiantAdministrateurSource, $identifiantUtilisateurCible, $roleApresTransfert): void {
            DB::table('compte_membre')
                ->where('identifiant', $identifiantUtilisateurCible)
                ->update([
                    'code_role' => User::ROLE_ADMIN,
                    'mis_a_jour_le' => date('Y-m-d H:i:s'),
                ]);

            DB::table('compte_membre')
                ->where('identifiant', $identifiantAdministrateurSource)
                ->update([
                    'code_role' => $roleApresTransfert,
                    'mis_a_jour_le' => date('Y-m-d H:i:s'),
                ]);
        });

        return $this->trouverParIdentifiant($identifiantUtilisateurCible);
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
            'date_naissance' => $this->formaterDateNaissance($row['date_naissance'] ?? null),
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

    private function normaliserDateNaissance(mixed $valeur): ?string
    {
        $date = trim((string) $valeur);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : null;
    }

    private function formaterDateNaissance(mixed $valeur): string
    {
        if ($valeur === null || $valeur === '') {
            return '';
        }

        try {
            return (new DateTimeImmutable((string) $valeur))->format('Y-m-d');
        } catch (\Throwable) {
            return '';
        }
    }

    private function compterAdministrateurs(): int
    {
        return (int) DB::table('compte_membre')
            ->where('code_role', User::ROLE_ADMIN)
            ->count();
    }

    private function compterProfesseurs(): int
    {
        return (int) DB::table('compte_membre')
            ->where('code_role', User::ROLE_PROF)
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
