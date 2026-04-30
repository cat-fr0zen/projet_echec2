<?php

declare(strict_types=1);

final class DepotUtilisateurs
{
    public function __construct(private StockageJson $stockage)
    {
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

        $utilisateur = [
            'identifiant' => 'utilisateur_' . bin2hex(random_bytes(8)),
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'date_naissance' => $donnees['date_naissance'],
            'courriel' => mb_strtolower(trim($donnees['courriel'])),
            'mot_de_passe_hache' => password_hash($donnees['mot_de_passe'], PASSWORD_DEFAULT),
            'description_profil' => $donnees['description_profil'],
            'pseudo_chess' => $this->normaliserPseudoChess($donnees['pseudo_chess'] ?? ''),
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
        return [
            'identifiant' => (string) ($enregistrement['identifiant'] ?? $enregistrement['id'] ?? ''),
            'nom' => (string) ($enregistrement['nom'] ?? $enregistrement['last_name'] ?? ''),
            'prenom' => (string) ($enregistrement['prenom'] ?? $enregistrement['first_name'] ?? ''),
            'date_naissance' => (string) ($enregistrement['date_naissance'] ?? $enregistrement['birth_date'] ?? ''),
            'courriel' => mb_strtolower((string) ($enregistrement['courriel'] ?? $enregistrement['email'] ?? '')),
            'mot_de_passe_hache' => (string) ($enregistrement['mot_de_passe_hache'] ?? $enregistrement['password_hash'] ?? ''),
            'description_profil' => (string) ($enregistrement['description_profil'] ?? $enregistrement['profile_description'] ?? ''),
            'pseudo_chess' => $this->normaliserPseudoChess((string) ($enregistrement['pseudo_chess'] ?? $enregistrement['chess_username'] ?? '')),
            'cree_le' => (string) ($enregistrement['cree_le'] ?? $enregistrement['created_at'] ?? ''),
            'mis_a_jour_le' => (string) ($enregistrement['mis_a_jour_le'] ?? $enregistrement['updated_at'] ?? ''),
        ];
    }

    private function normaliserPseudoChess(mixed $valeur): string
    {
        return mb_strtolower(trim((string) $valeur));
    }
}
