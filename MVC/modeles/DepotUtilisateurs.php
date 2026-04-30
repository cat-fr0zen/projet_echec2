<?php

declare(strict_types=1);

final class DepotUtilisateurs
{
    public const ROLE_CONNECTE = 'connecte';
    public const ROLE_ADHERENT = 'adherent';
    public const ROLE_ADMIN = 'admin';

    public const STATUT_COMPTE_ACTIF = 'actif';
    public const STATUT_COMPTE_SUSPENDU = 'suspendu';

    public const STATUT_ADHESION_AUCUNE = 'aucune';
    public const STATUT_ADHESION_ACTIVE = 'active';

    public function __construct(private StockageJson $stockage)
    {
        $this->migrerDonneesExistantes();
    }

    public function trouverParIdentifiant(?string $identifiant): ?array
    {
        if ($identifiant === null || $identifiant === '') {
            return null;
        }

        foreach ($this->stockage->lire() as $enregistrement) {
            $utilisateur = $this->normaliserUtilisateur($enregistrement);

            if (($utilisateur['identifiant'] ?? '') === $identifiant) {
                return $utilisateur;
            }
        }

        return null;
    }

    public function listerTous(): array
    {
        return array_map(
            fn (array $enregistrement): array => $this->normaliserUtilisateur($enregistrement),
            $this->stockage->lire()
        );
    }

    public function trouverParCourriel(string $courriel): ?array
    {
        $courrielNormalise = mb_strtolower(trim($courriel));

        foreach ($this->stockage->lire() as $enregistrement) {
            $utilisateur = $this->normaliserUtilisateur($enregistrement);

            if (($utilisateur['courriel'] ?? '') === $courrielNormalise) {
                return $utilisateur;
            }
        }

        return null;
    }

    public function creer(array $donnees): array
    {
        $utilisateurs = $this->stockage->lire();
        $estPremierCompte = $utilisateurs === [];

        $utilisateur = [
            'identifiant' => 'utilisateur_' . bin2hex(random_bytes(8)),
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'date_naissance' => $donnees['date_naissance'],
            'courriel' => mb_strtolower(trim($donnees['courriel'])),
            'mot_de_passe_hache' => password_hash($donnees['mot_de_passe'], PASSWORD_DEFAULT),
            'description_profil' => $donnees['description_profil'],
            'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
            'role' => $estPremierCompte ? self::ROLE_ADMIN : self::ROLE_CONNECTE,
            'statut_compte' => self::STATUT_COMPTE_ACTIF,
            'statut_adhesion' => $estPremierCompte ? self::STATUT_ADHESION_ACTIVE : self::STATUT_ADHESION_AUCUNE,
            'cree_le' => gmdate('c'),
        ];

        $utilisateurs[] = $utilisateur;
        $this->stockage->ecrire($utilisateurs);

        return $utilisateur;
    }

    public function mettreAJour(string $identifiant, array $donnees): ?array
    {
        $utilisateurs = $this->stockage->lire();

        foreach ($utilisateurs as $index => $enregistrement) {
            $utilisateur = $this->normaliserUtilisateur($enregistrement);

            if (($utilisateur['identifiant'] ?? '') !== $identifiant) {
                continue;
            }

            $utilisateurs[$index] = [
                'identifiant' => $utilisateur['identifiant'],
                'nom' => $donnees['nom'],
                'prenom' => $donnees['prenom'],
                'date_naissance' => $donnees['date_naissance'],
                'courriel' => $utilisateur['courriel'],
                'mot_de_passe_hache' => $utilisateur['mot_de_passe_hache'],
                'description_profil' => $donnees['description_profil'],
                'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
                'role' => $utilisateur['role'],
                'statut_compte' => $utilisateur['statut_compte'],
                'statut_adhesion' => $utilisateur['statut_adhesion'],
                'cree_le' => $utilisateur['cree_le'],
                'mis_a_jour_le' => gmdate('c'),
            ];

            $this->stockage->ecrire($utilisateurs);

            return $this->normaliserUtilisateur($utilisateurs[$index]);
        }

        return null;
    }

    public function mettreAJourAcces(string $identifiant, string $role, string $statutCompte, string $statutAdhesion): ?array
    {
        if (!in_array($role, [self::ROLE_CONNECTE, self::ROLE_ADHERENT, self::ROLE_ADMIN], true)) {
            return null;
        }

        if (!in_array($statutCompte, [self::STATUT_COMPTE_ACTIF, self::STATUT_COMPTE_SUSPENDU], true)) {
            return null;
        }

        if (!in_array($statutAdhesion, [self::STATUT_ADHESION_AUCUNE, self::STATUT_ADHESION_ACTIVE], true)) {
            return null;
        }

        $utilisateurs = $this->stockage->lire();

        foreach ($utilisateurs as $index => $enregistrement) {
            $utilisateur = $this->normaliserUtilisateur($enregistrement);

            if (($utilisateur['identifiant'] ?? '') !== $identifiant) {
                continue;
            }

            if (
                $utilisateur['role'] === self::ROLE_ADMIN
                && $role !== self::ROLE_ADMIN
                && $this->compterAdministrateurs() <= 1
            ) {
                return null;
            }

            $utilisateurs[$index] = [
                'identifiant' => $utilisateur['identifiant'],
                'nom' => $utilisateur['nom'],
                'prenom' => $utilisateur['prenom'],
                'date_naissance' => $utilisateur['date_naissance'],
                'courriel' => $utilisateur['courriel'],
                'mot_de_passe_hache' => $utilisateur['mot_de_passe_hache'],
                'description_profil' => $utilisateur['description_profil'],
                'pseudo_chess' => $utilisateur['pseudo_chess'],
                'role' => $role,
                'statut_compte' => $statutCompte,
                'statut_adhesion' => $statutAdhesion,
                'cree_le' => $utilisateur['cree_le'],
                'mis_a_jour_le' => gmdate('c'),
            ];

            $this->stockage->ecrire($utilisateurs);

            return $this->normaliserUtilisateur($utilisateurs[$index]);
        }

        return null;
    }

    private function normaliserUtilisateur(array $enregistrement): array
    {
        $role = (string) ($enregistrement['role'] ?? $enregistrement['code_role'] ?? self::ROLE_CONNECTE);
        $statutCompte = (string) ($enregistrement['statut_compte'] ?? $enregistrement['account_status'] ?? self::STATUT_COMPTE_ACTIF);
        $statutAdhesion = (string) ($enregistrement['statut_adhesion'] ?? $enregistrement['membership_status'] ?? self::STATUT_ADHESION_AUCUNE);

        if (!in_array($role, [self::ROLE_CONNECTE, self::ROLE_ADHERENT, self::ROLE_ADMIN], true)) {
            $role = self::ROLE_CONNECTE;
        }

        if (!in_array($statutCompte, [self::STATUT_COMPTE_ACTIF, self::STATUT_COMPTE_SUSPENDU], true)) {
            $statutCompte = self::STATUT_COMPTE_ACTIF;
        }

        if (!in_array($statutAdhesion, [self::STATUT_ADHESION_AUCUNE, self::STATUT_ADHESION_ACTIVE], true)) {
            $statutAdhesion = self::STATUT_ADHESION_AUCUNE;
        }

        return [
            'identifiant' => (string) ($enregistrement['identifiant'] ?? $enregistrement['id'] ?? ''),
            'nom' => (string) ($enregistrement['nom'] ?? $enregistrement['last_name'] ?? ''),
            'prenom' => (string) ($enregistrement['prenom'] ?? $enregistrement['first_name'] ?? ''),
            'date_naissance' => (string) ($enregistrement['date_naissance'] ?? $enregistrement['birth_date'] ?? ''),
            'courriel' => mb_strtolower((string) ($enregistrement['courriel'] ?? $enregistrement['email'] ?? '')),
            'mot_de_passe_hache' => (string) ($enregistrement['mot_de_passe_hache'] ?? $enregistrement['password_hash'] ?? ''),
            'description_profil' => (string) ($enregistrement['description_profil'] ?? $enregistrement['profile_description'] ?? ''),
            'pseudo_chess' => $this->normaliserPseudoChess((string) ($enregistrement['pseudo_chess'] ?? $enregistrement['chess_username'] ?? '')),
            'role' => $role,
            'statut_compte' => $statutCompte,
            'statut_adhesion' => $statutAdhesion,
            'cree_le' => (string) ($enregistrement['cree_le'] ?? $enregistrement['created_at'] ?? ''),
            'mis_a_jour_le' => (string) ($enregistrement['mis_a_jour_le'] ?? $enregistrement['updated_at'] ?? ''),
        ];
    }

    private function normaliserPseudoChess(mixed $valeur): string
    {
        return mb_strtolower(trim((string) $valeur));
    }

    private function compterAdministrateurs(): int
    {
        $compteur = 0;

        foreach ($this->stockage->lire() as $enregistrement) {
            $utilisateur = $this->normaliserUtilisateur($enregistrement);

            if ($utilisateur['role'] === self::ROLE_ADMIN) {
                $compteur++;
            }
        }

        return $compteur;
    }

    private function migrerDonneesExistantes(): void
    {
        $utilisateurs = $this->stockage->lire();
        $donneesModifiees = false;

        foreach ($utilisateurs as $index => $enregistrement) {
            $roleDefini = isset($enregistrement['role']) || isset($enregistrement['code_role']);
            $statutCompteDefini = isset($enregistrement['statut_compte']) || isset($enregistrement['account_status']);
            $statutAdhesionDefini = isset($enregistrement['statut_adhesion']) || isset($enregistrement['membership_status']);

            if ($roleDefini && $statutCompteDefini && $statutAdhesionDefini) {
                continue;
            }

            $utilisateurNormalise = $this->normaliserUtilisateur($enregistrement);
            $utilisateurs[$index] = [
                'identifiant' => $utilisateurNormalise['identifiant'],
                'nom' => $utilisateurNormalise['nom'],
                'prenom' => $utilisateurNormalise['prenom'],
                'date_naissance' => $utilisateurNormalise['date_naissance'],
                'courriel' => $utilisateurNormalise['courriel'],
                'mot_de_passe_hache' => $utilisateurNormalise['mot_de_passe_hache'],
                'description_profil' => $utilisateurNormalise['description_profil'],
                'pseudo_chess' => $utilisateurNormalise['pseudo_chess'],
                'role' => $index === 0 ? self::ROLE_ADMIN : self::ROLE_CONNECTE,
                'statut_compte' => self::STATUT_COMPTE_ACTIF,
                'statut_adhesion' => $index === 0 ? self::STATUT_ADHESION_ACTIVE : self::STATUT_ADHESION_AUCUNE,
                'cree_le' => $utilisateurNormalise['cree_le'] !== '' ? $utilisateurNormalise['cree_le'] : gmdate('c'),
                'mis_a_jour_le' => gmdate('c'),
            ];
            $donneesModifiees = true;
        }

        if ($donneesModifiees) {
            $this->stockage->ecrire($utilisateurs);
        }
    }
}
