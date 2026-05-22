<?php

declare(strict_types=1);

final class DepotUtilisateursOracle
{
    public function __construct(private BaseDeDonneesOracle $base)
    {
    }

    public function trouverParIdentifiant(?string $identifiant): ?array
    {
        if ($identifiant === null || trim($identifiant) === '') {
            return null;
        }

        $ligne = $this->base->lireUne($this->sqlUtilisateur('WHERE identifiant = :identifiant'), [
            'identifiant' => $identifiant,
        ]);

        return $ligne !== null ? $this->normaliserUtilisateur($ligne) : null;
    }

    public function listerTous(): array
    {
        return array_map(
            fn (array $ligne): array => $this->normaliserUtilisateur($ligne),
            $this->base->lireTout($this->sqlUtilisateur('ORDER BY cree_le DESC'))
        );
    }

    public function trouverParCourriel(string $courriel): ?array
    {
        $ligne = $this->base->lireUne($this->sqlUtilisateur('WHERE courriel_normalise = LOWER(TRIM(:courriel))'), [
            'courriel' => $courriel,
        ]);

        return $ligne !== null ? $this->normaliserUtilisateur($ligne) : null;
    }

    public function trouverParNumeroLicence(string $numeroLicence): ?array
    {
        $numeroNormalise = $this->normaliserNumeroLicenceFederale($numeroLicence);

        if ($numeroNormalise === '') {
            return null;
        }

        $ligne = $this->base->lireUne($this->sqlUtilisateur('WHERE UPPER(numero_licence_federale) = :numero_licence'), [
            'numero_licence' => $numeroNormalise,
        ]);

        return $ligne !== null ? $this->normaliserUtilisateur($ligne) : null;
    }

    public function trouverParIdentifiantConnexion(string $identifiantConnexion): ?array
    {
        $identifiant = trim($identifiantConnexion);

        if ($identifiant === '') {
            return null;
        }

        if (filter_var($identifiant, FILTER_VALIDATE_EMAIL)) {
            return $this->trouverParCourriel($identifiant);
        }

        return $this->trouverParNumeroLicence($identifiant);
    }

    public function creer(array $donnees): array
    {
        $nombreComptes = (int) ($this->base->lireUne('SELECT COUNT(*) nombre FROM compte_membre')['nombre'] ?? 0);
        $estPremierCompte = $nombreComptes === 0;
        $identifiant = 'utilisateur_' . bin2hex(random_bytes(8));
        $numeroLicence = $this->normaliserNumeroLicenceFederale($donnees['numero_licence'] ?? '');

        $this->base->executer(
            'INSERT INTO compte_membre (
                identifiant, nom, prenom, date_naissance, courriel, courriel_normalise,
                numero_licence_federale, mot_de_passe_hache, description_profil, pseudo_chess,
                code_role, code_statut_compte, code_statut_adhesion, cree_le
            ) VALUES (
                :identifiant, :nom, :prenom, :date_naissance, :courriel, LOWER(TRIM(:courriel_normalise)),
                :numero_licence, :mot_de_passe_hache, :description_profil, :pseudo_chess,
                :code_role, :code_statut_compte, :code_statut_adhesion, SYSDATE
            )',
            [
                'identifiant' => $identifiant,
                'nom' => (string) $donnees['nom'],
                'prenom' => (string) $donnees['prenom'],
                'date_naissance' => (string) $donnees['date_naissance'],
                'courriel' => mb_strtolower(trim((string) $donnees['courriel'])),
                'courriel_normalise' => mb_strtolower(trim((string) $donnees['courriel'])),
                'numero_licence' => $numeroLicence !== '' ? $numeroLicence : null,
                'mot_de_passe_hache' => password_hash((string) $donnees['mot_de_passe'], PASSWORD_DEFAULT),
                'description_profil' => (string) $donnees['description_profil'],
                'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
                'code_role' => $estPremierCompte ? DepotUtilisateurs::ROLE_ADMIN : DepotUtilisateurs::ROLE_CONNECTE,
                'code_statut_compte' => DepotUtilisateurs::STATUT_COMPTE_ACTIF,
                'code_statut_adhesion' => $estPremierCompte ? DepotUtilisateurs::STATUT_ADHESION_ACTIVE : DepotUtilisateurs::STATUT_ADHESION_AUCUNE,
            ]
        );

        return $this->trouverParIdentifiant($identifiant) ?? [];
    }

    public function mettreAJour(string $identifiant, array $donnees): ?array
    {
        $numeroLicence = $this->normaliserNumeroLicenceFederale($donnees['numero_licence'] ?? '');

        $lignes = $this->base->executer(
            'UPDATE compte_membre
                SET nom = :nom,
                    prenom = :prenom,
                    date_naissance = :date_naissance,
                    numero_licence_federale = :numero_licence,
                    description_profil = :description_profil,
                    pseudo_chess = :pseudo_chess,
                    mis_a_jour_le = SYSDATE
              WHERE identifiant = :identifiant',
            [
                'nom' => (string) $donnees['nom'],
                'prenom' => (string) $donnees['prenom'],
                'date_naissance' => (string) $donnees['date_naissance'],
                'numero_licence' => $numeroLicence !== '' ? $numeroLicence : null,
                'description_profil' => (string) $donnees['description_profil'],
                'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
                'identifiant' => $identifiant,
            ]
        );

        return $lignes > 0 ? $this->trouverParIdentifiant($identifiant) : null;
    }

    public function mettreAJourAcces(string $identifiant, string $role, string $statutCompte, string $statutAdhesion): ?array
    {
        if (!in_array($role, [DepotUtilisateurs::ROLE_CONNECTE, DepotUtilisateurs::ROLE_ADHERENT, DepotUtilisateurs::ROLE_ADMIN], true)) {
            return null;
        }

        if (!in_array($statutCompte, [DepotUtilisateurs::STATUT_COMPTE_ACTIF, DepotUtilisateurs::STATUT_COMPTE_SUSPENDU], true)) {
            return null;
        }

        if (!in_array($statutAdhesion, [DepotUtilisateurs::STATUT_ADHESION_AUCUNE, DepotUtilisateurs::STATUT_ADHESION_ACTIVE], true)) {
            return null;
        }

        $utilisateur = $this->trouverParIdentifiant($identifiant);

        if ($utilisateur === null) {
            return null;
        }

        if (
            $utilisateur['role'] === DepotUtilisateurs::ROLE_ADMIN
            && $role !== DepotUtilisateurs::ROLE_ADMIN
            && $this->compterAdministrateurs() <= 1
        ) {
            return null;
        }

        $this->base->executer(
            'UPDATE compte_membre
                SET code_role = :code_role,
                    code_statut_compte = :code_statut_compte,
                    code_statut_adhesion = :code_statut_adhesion,
                    mis_a_jour_le = SYSDATE
              WHERE identifiant = :identifiant',
            [
                'code_role' => $role,
                'code_statut_compte' => $statutCompte,
                'code_statut_adhesion' => $statutAdhesion,
                'identifiant' => $identifiant,
            ]
        );

        return $this->trouverParIdentifiant($identifiant);
    }

    public function normaliserNumeroLicenceFederale(mixed $valeur): string
    {
        $numero = mb_strtoupper(trim((string) $valeur));

        return preg_replace('/\s+/', '', $numero) ?? '';
    }

    private function sqlUtilisateur(string $suffixe = ''): string
    {
        return 'SELECT
                identifiant,
                nom,
                prenom,
                date_naissance,
                courriel,
                numero_licence_federale,
                mot_de_passe_hache,
                description_profil,
                pseudo_chess,
                code_role,
                code_statut_compte,
                code_statut_adhesion,
                TO_CHAR(cree_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') cree_le,
                TO_CHAR(mis_a_jour_le, \'YYYY-MM-DD"T"HH24:MI:SS"Z"\') mis_a_jour_le
            FROM compte_membre ' . $suffixe;
    }

    private function normaliserUtilisateur(array $ligne): array
    {
        return [
            'identifiant' => (string) ($ligne['identifiant'] ?? ''),
            'nom' => (string) ($ligne['nom'] ?? ''),
            'prenom' => (string) ($ligne['prenom'] ?? ''),
            'date_naissance' => (string) ($ligne['date_naissance'] ?? ''),
            'courriel' => mb_strtolower((string) ($ligne['courriel'] ?? '')),
            'numero_licence' => $this->normaliserNumeroLicenceFederale($ligne['numero_licence_federale'] ?? ''),
            'mot_de_passe_hache' => (string) ($ligne['mot_de_passe_hache'] ?? ''),
            'description_profil' => (string) ($ligne['description_profil'] ?? ''),
            'pseudo_chess' => $this->normaliserPseudoChess($ligne['pseudo_chess'] ?? ''),
            'role' => (string) ($ligne['code_role'] ?? DepotUtilisateurs::ROLE_CONNECTE),
            'statut_compte' => (string) ($ligne['code_statut_compte'] ?? DepotUtilisateurs::STATUT_COMPTE_ACTIF),
            'statut_adhesion' => (string) ($ligne['code_statut_adhesion'] ?? DepotUtilisateurs::STATUT_ADHESION_AUCUNE),
            'cree_le' => (string) ($ligne['cree_le'] ?? ''),
            'mis_a_jour_le' => (string) ($ligne['mis_a_jour_le'] ?? ''),
        ];
    }

    private function normaliserPseudoChess(mixed $valeur): string
    {
        return mb_strtolower(trim((string) $valeur));
    }

    private function compterAdministrateurs(): int
    {
        $ligne = $this->base->lireUne(
            'SELECT COUNT(*) nombre FROM compte_membre WHERE code_role = :code_role',
            ['code_role' => DepotUtilisateurs::ROLE_ADMIN]
        );

        return (int) ($ligne['nombre'] ?? 0);
    }
}
