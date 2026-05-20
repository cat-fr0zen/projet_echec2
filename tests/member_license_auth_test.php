<?php

declare(strict_types=1);

$racineProjet = dirname(__DIR__);

require_once $racineProjet . '/MVC/modeles/StockageJson.php';
require_once $racineProjet . '/MVC/modeles/DepotUtilisateurs.php';

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[ERREUR] {$message}" . PHP_EOL);
        exit(1);
    }
}

$fichierTemporaire = $racineProjet . '/tests/tmp_member_license_users.json';

if (file_exists($fichierTemporaire)) {
    unlink($fichierTemporaire);
}

$depot = new DepotUtilisateurs(new StockageJson($fichierTemporaire));

$utilisateur = $depot->creer([
    'nom' => 'Test',
    'prenom' => 'Licence',
    'date_naissance' => '',
    'courriel' => 'licence.test@example.org',
    'numero_licence' => ' ffe-12345 ',
    'mot_de_passe' => 'motdepasse-solide',
    'description_profil' => '',
    'pseudo_chess' => '',
]);

verifier(($utilisateur['numero_licence'] ?? '') === 'FFE-12345', 'Le numero de licence doit etre normalise.');
verifier(
    ($depot->trouverParNumeroLicence('ffe-12345')['identifiant'] ?? '') === $utilisateur['identifiant'],
    'La recherche par numero de licence doit ignorer la casse.'
);
verifier(
    ($depot->trouverParIdentifiantConnexion('ffe-12345')['identifiant'] ?? '') === $utilisateur['identifiant'],
    'La connexion doit accepter le numero de licence.'
);
verifier(
    ($depot->trouverParIdentifiantConnexion('licence.test@example.org')['identifiant'] ?? '') === $utilisateur['identifiant'],
    'La connexion par email doit rester disponible.'
);

$utilisateurMisAJour = $depot->mettreAJour($utilisateur['identifiant'], [
    'nom' => 'Test',
    'prenom' => 'Licence',
    'date_naissance' => '',
    'numero_licence' => 'ffe-98765',
    'description_profil' => '',
    'pseudo_chess' => '',
]);

verifier(($utilisateurMisAJour['numero_licence'] ?? '') === 'FFE-98765', 'La mise a jour du profil doit conserver la licence normalisee.');

unlink($fichierTemporaire);

echo 'Authentification par licence OK.' . PHP_EOL;
