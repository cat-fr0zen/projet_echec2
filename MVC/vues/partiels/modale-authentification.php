<?php
/**
 * Partiel: Modale d'authentification.
 *
 * Gere les formulaires:
 * - connexion
 * - inscription
 *
 * Les erreurs/valeurs precedentes sont stockees en session via `etat_formulaire`
 * (helpers dans index.php).
 */
$donneesAuthentification = $donneesSite['authentification'];
$etatFormulaire = $donneesSite['etat_formulaire'] ?? [];
$modaleAuthentification = $donneesSite['modale_authentification'] ?? $donneesSite['auth_modal'];
$modaleOuverte = !empty($etatFormulaire['ouverte']) ? 'true' : 'false';
$ongletActif = isset($etatFormulaire['onglet']) && $etatFormulaire['onglet'] === 'inscription' ? 'inscription' : 'connexion';
$erreursFormulaire = $etatFormulaire['erreurs'] ?? [];
$anciennesValeurs = $etatFormulaire['anciennes_valeurs'] ?? [];
$resumeErreursId = 'auth-errors-summary';
$erreursParChamp = [];

foreach ($erreursFormulaire as $erreurBrute) {
    $erreur = (string) $erreurBrute;
    $erreurMinuscule = mb_strtolower($erreur);

    if (str_contains($erreurMinuscule, 'email ou mot de passe incorrect')) {
        $erreursParChamp['courriel'][] = $erreur;
        $erreursParChamp['mot_de_passe'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'mot de passe')) {
        $erreursParChamp['mot_de_passe'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'adresse email') || str_contains($erreurMinuscule, 'cet email')) {
        $erreursParChamp['courriel'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'prenom')) {
        $erreursParChamp['prenom'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'nom est obligatoire')) {
        $erreursParChamp['nom'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'date de naissance')) {
        $erreursParChamp['date_naissance'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'description de profil')) {
        $erreursParChamp['description_profil'][] = $erreur;
        continue;
    }

    if (str_contains($erreurMinuscule, 'chess.com')) {
        $erreursParChamp['pseudo_chess'][] = $erreur;
    }
}

/**
 * Retourne les metadonnees d'accessibilite d'un champ.
 *
 * @param string $idChamp Identifiant DOM du champ.
 * @param string $cleErreur Cle logique de regroupement des erreurs.
 * @return array{id: string, label_id: string, error_id: string, describedby: string, invalid: bool, error_message: string}
 */
$construireMetaChamp = static function (string $idChamp, string $cleErreur) use ($erreursParChamp, $erreursFormulaire, $resumeErreursId): array {
    $messages = $erreursParChamp[$cleErreur] ?? [];
    $errorId = $idChamp . '-error';
    $describedBy = [];

    if ($messages !== []) {
        $describedBy[] = $errorId;
    }

    if ($erreursFormulaire !== []) {
        $describedBy[] = $resumeErreursId;
    }

    return [
        'id' => $idChamp,
        'label_id' => $idChamp . '-label',
        'error_id' => $errorId,
        'describedby' => implode(' ', $describedBy),
        'invalid' => $messages !== [],
        'error_message' => $messages[0] ?? '',
    ];
};

$champConnexionCourriel = $construireMetaChamp('auth-login-email', 'courriel');
$champConnexionMotDePasse = $construireMetaChamp('auth-login-password', 'mot_de_passe');
$champInscriptionNom = $construireMetaChamp('auth-register-last-name', 'nom');
$champInscriptionPrenom = $construireMetaChamp('auth-register-first-name', 'prenom');
$champInscriptionNaissance = $construireMetaChamp('auth-register-birth-date', 'date_naissance');
$champInscriptionCourriel = $construireMetaChamp('auth-register-email', 'courriel');
$champInscriptionMotDePasse = $construireMetaChamp('auth-register-password', 'mot_de_passe');
$champInscriptionPseudoChess = $construireMetaChamp('auth-register-chess-username', 'pseudo_chess');
$champInscriptionDescription = $construireMetaChamp('auth-register-description', 'description_profil');
?>

<?php if (!$donneesAuthentification['est_connecte']): ?>
    <div
        class="auth-modal"
        data-auth-modal
        data-auth-open-state="<?= e($modaleOuverte) ?>"
        data-auth-tab="<?= e($ongletActif) ?>"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="auth-modal-title"
        aria-describedby="auth-modal-description"
    >
        <div class="auth-modal-panel">
            <button type="button" class="auth-close" data-auth-close aria-label="Fermer la fenêtre">×</button>
            <p class="eyebrow">Espace membre</p>
            <h2 id="auth-modal-title"><?= e($modaleAuthentification['title']) ?></h2>
            <p id="auth-modal-description" class="auth-modal-description">
                Connexion rapide par email. La création de compte reste disponible dans la même fenêtre, sans surcharger les pages.
            </p>

            <div class="auth-tab-row" role="tablist" aria-label="Connexion ou création de compte">
                <button
                    type="button"
                    class="auth-tab-button"
                    data-auth-tab-trigger="connexion"
                    role="tab"
                    id="auth-tab-connexion"
                    aria-controls="auth-panel-connexion"
                    aria-selected="<?= $ongletActif === 'connexion' ? 'true' : 'false' ?>"
                    tabindex="<?= $ongletActif === 'connexion' ? '0' : '-1' ?>"
                >
                    Connexion
                </button>
                <button
                    type="button"
                    class="auth-tab-button auth-tab-button--muted"
                    data-auth-tab-trigger="inscription"
                    role="tab"
                    id="auth-tab-inscription"
                    aria-controls="auth-panel-inscription"
                    aria-selected="<?= $ongletActif === 'inscription' ? 'true' : 'false' ?>"
                    tabindex="<?= $ongletActif === 'inscription' ? '0' : '-1' ?>"
                >
                    Créer un compte
                </button>
            </div>

            <?php if ($erreursFormulaire !== []): ?>
                <div id="<?= e($resumeErreursId) ?>" class="auth-errors" role="alert" tabindex="-1">
                    <?php foreach ($erreursFormulaire as $erreur): ?>
                        <p><?= e($erreur) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="auth-panels">
                <form
                    method="post"
                    action="<?= e(url_route($pageCourante)) ?>"
                    class="auth-form"
                    data-auth-panel="connexion"
                    id="auth-panel-connexion"
                    role="tabpanel"
                    aria-labelledby="auth-tab-connexion"
                    <?= $erreursFormulaire !== [] ? 'aria-describedby="' . e($resumeErreursId) . '"' : '' ?>
                    <?= $ongletActif !== 'connexion' ? 'hidden' : '' ?>
                >
                    <input type="hidden" name="action" value="connexion">
                    <input type="hidden" name="jeton_csrf" value="<?= e($donneesSite['jeton_csrf']) ?>">
                    <input type="hidden" name="page_redirection" value="<?= e($pageCourante) ?>">

                    <label class="form-group">
                        <span id="<?= e($champConnexionCourriel['label_id']) ?>">Email</span>
                        <input
                            id="<?= e($champConnexionCourriel['id']) ?>"
                            type="email"
                            name="courriel"
                            required
                            autocomplete="email"
                            value="<?= e((string) ($anciennesValeurs['courriel'] ?? '')) ?>"
                            aria-labelledby="<?= e($champConnexionCourriel['label_id']) ?>"
                            <?= $champConnexionCourriel['describedby'] !== '' ? 'aria-describedby="' . e($champConnexionCourriel['describedby']) . '"' : '' ?>
                            <?= $champConnexionCourriel['invalid'] ? 'aria-invalid="true"' : '' ?>
                        >
                        <?php if ($champConnexionCourriel['error_message'] !== ''): ?>
                            <span id="<?= e($champConnexionCourriel['error_id']) ?>" class="form-error">
                                <?= e($champConnexionCourriel['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <label class="form-group">
                        <span id="<?= e($champConnexionMotDePasse['label_id']) ?>">Mot de passe</span>
                        <input
                            id="<?= e($champConnexionMotDePasse['id']) ?>"
                            type="password"
                            name="mot_de_passe"
                            required
                            minlength="8"
                            autocomplete="current-password"
                            aria-labelledby="<?= e($champConnexionMotDePasse['label_id']) ?>"
                            <?= $champConnexionMotDePasse['describedby'] !== '' ? 'aria-describedby="' . e($champConnexionMotDePasse['describedby']) . '"' : '' ?>
                            <?= $champConnexionMotDePasse['invalid'] ? 'aria-invalid="true"' : '' ?>
                        >
                        <?php if ($champConnexionMotDePasse['error_message'] !== ''): ?>
                            <span id="<?= e($champConnexionMotDePasse['error_id']) ?>" class="form-error">
                                <?= e($champConnexionMotDePasse['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="button button-primary auth-submit">Se connecter</button>
                </form>

                <form
                    method="post"
                    action="<?= e(url_route($pageCourante)) ?>"
                    class="auth-form auth-form--register"
                    data-auth-panel="inscription"
                    id="auth-panel-inscription"
                    role="tabpanel"
                    aria-labelledby="auth-tab-inscription"
                    <?= $erreursFormulaire !== [] ? 'aria-describedby="' . e($resumeErreursId) . '"' : '' ?>
                    <?= $ongletActif !== 'inscription' ? 'hidden' : '' ?>
                >
                    <input type="hidden" name="action" value="inscription">
                    <input type="hidden" name="jeton_csrf" value="<?= e($donneesSite['jeton_csrf']) ?>">
                    <input type="hidden" name="page_redirection" value="<?= e($pageCourante) ?>">

                    <div class="auth-grid">
                        <label class="form-group">
                            <span id="<?= e($champInscriptionNom['label_id']) ?>">Nom</span>
                            <input
                                id="<?= e($champInscriptionNom['id']) ?>"
                                type="text"
                                name="nom"
                                required
                                maxlength="100"
                                autocomplete="family-name"
                                value="<?= e((string) ($anciennesValeurs['nom'] ?? '')) ?>"
                                aria-labelledby="<?= e($champInscriptionNom['label_id']) ?>"
                                <?= $champInscriptionNom['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionNom['describedby']) . '"' : '' ?>
                                <?= $champInscriptionNom['invalid'] ? 'aria-invalid="true"' : '' ?>
                            >
                            <?php if ($champInscriptionNom['error_message'] !== ''): ?>
                                <span id="<?= e($champInscriptionNom['error_id']) ?>" class="form-error">
                                    <?= e($champInscriptionNom['error_message']) ?>
                                </span>
                            <?php endif; ?>
                        </label>

                        <label class="form-group">
                            <span id="<?= e($champInscriptionPrenom['label_id']) ?>">Prénom</span>
                            <input
                                id="<?= e($champInscriptionPrenom['id']) ?>"
                                type="text"
                                name="prenom"
                                required
                                maxlength="100"
                                autocomplete="given-name"
                                value="<?= e((string) ($anciennesValeurs['prenom'] ?? '')) ?>"
                                aria-labelledby="<?= e($champInscriptionPrenom['label_id']) ?>"
                                <?= $champInscriptionPrenom['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionPrenom['describedby']) . '"' : '' ?>
                                <?= $champInscriptionPrenom['invalid'] ? 'aria-invalid="true"' : '' ?>
                            >
                            <?php if ($champInscriptionPrenom['error_message'] !== ''): ?>
                                <span id="<?= e($champInscriptionPrenom['error_id']) ?>" class="form-error">
                                    <?= e($champInscriptionPrenom['error_message']) ?>
                                </span>
                            <?php endif; ?>
                        </label>
                    </div>

                    <label class="form-group">
                        <span id="<?= e($champInscriptionNaissance['label_id']) ?>">Date de naissance facultative</span>
                        <input
                            id="<?= e($champInscriptionNaissance['id']) ?>"
                            type="date"
                            name="date_naissance"
                            autocomplete="bday"
                            value="<?= e((string) ($anciennesValeurs['date_naissance'] ?? '')) ?>"
                            aria-labelledby="<?= e($champInscriptionNaissance['label_id']) ?>"
                            <?= $champInscriptionNaissance['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionNaissance['describedby']) . '"' : '' ?>
                            <?= $champInscriptionNaissance['invalid'] ? 'aria-invalid="true"' : '' ?>
                        >
                        <?php if ($champInscriptionNaissance['error_message'] !== ''): ?>
                            <span id="<?= e($champInscriptionNaissance['error_id']) ?>" class="form-error">
                                <?= e($champInscriptionNaissance['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <label class="form-group">
                        <span id="<?= e($champInscriptionCourriel['label_id']) ?>">Email</span>
                        <input
                            id="<?= e($champInscriptionCourriel['id']) ?>"
                            type="email"
                            name="courriel"
                            required
                            autocomplete="email"
                            value="<?= e((string) ($anciennesValeurs['courriel'] ?? '')) ?>"
                            aria-labelledby="<?= e($champInscriptionCourriel['label_id']) ?>"
                            <?= $champInscriptionCourriel['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionCourriel['describedby']) . '"' : '' ?>
                            <?= $champInscriptionCourriel['invalid'] ? 'aria-invalid="true"' : '' ?>
                        >
                        <?php if ($champInscriptionCourriel['error_message'] !== ''): ?>
                            <span id="<?= e($champInscriptionCourriel['error_id']) ?>" class="form-error">
                                <?= e($champInscriptionCourriel['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <label class="form-group">
                        <span id="<?= e($champInscriptionMotDePasse['label_id']) ?>">Mot de passe</span>
                        <input
                            id="<?= e($champInscriptionMotDePasse['id']) ?>"
                            type="password"
                            name="mot_de_passe"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            aria-labelledby="<?= e($champInscriptionMotDePasse['label_id']) ?>"
                            <?= $champInscriptionMotDePasse['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionMotDePasse['describedby']) . '"' : '' ?>
                            <?= $champInscriptionMotDePasse['invalid'] ? 'aria-invalid="true"' : '' ?>
                        >
                        <?php if ($champInscriptionMotDePasse['error_message'] !== ''): ?>
                            <span id="<?= e($champInscriptionMotDePasse['error_id']) ?>" class="form-error">
                                <?= e($champInscriptionMotDePasse['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <label class="form-group">
                        <span id="<?= e($champInscriptionPseudoChess['label_id']) ?>">Pseudo Chess.com facultatif</span>
                        <input
                            id="<?= e($champInscriptionPseudoChess['id']) ?>"
                            type="text"
                            name="pseudo_chess"
                            maxlength="50"
                            autocomplete="off"
                            value="<?= e((string) ($anciennesValeurs['pseudo_chess'] ?? '')) ?>"
                            aria-labelledby="<?= e($champInscriptionPseudoChess['label_id']) ?>"
                            <?= $champInscriptionPseudoChess['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionPseudoChess['describedby']) . '"' : '' ?>
                            <?= $champInscriptionPseudoChess['invalid'] ? 'aria-invalid="true"' : '' ?>
                        >
                        <?php if ($champInscriptionPseudoChess['error_message'] !== ''): ?>
                            <span id="<?= e($champInscriptionPseudoChess['error_id']) ?>" class="form-error">
                                <?= e($champInscriptionPseudoChess['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <label class="form-group">
                        <span id="<?= e($champInscriptionDescription['label_id']) ?>">Description du profil</span>
                        <textarea
                            id="<?= e($champInscriptionDescription['id']) ?>"
                            name="description_profil"
                            rows="4"
                            maxlength="1200"
                            aria-labelledby="<?= e($champInscriptionDescription['label_id']) ?>"
                            <?= $champInscriptionDescription['describedby'] !== '' ? 'aria-describedby="' . e($champInscriptionDescription['describedby']) . '"' : '' ?>
                            <?= $champInscriptionDescription['invalid'] ? 'aria-invalid="true"' : '' ?>
                        ><?= e((string) ($anciennesValeurs['description_profil'] ?? '')) ?></textarea>
                        <?php if ($champInscriptionDescription['error_message'] !== ''): ?>
                            <span id="<?= e($champInscriptionDescription['error_id']) ?>" class="form-error">
                                <?= e($champInscriptionDescription['error_message']) ?>
                            </span>
                        <?php endif; ?>
                    </label>

                    <button type="submit" class="button button-primary auth-submit">Créer le compte</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
