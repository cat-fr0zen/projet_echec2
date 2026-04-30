<?php
$donneesAuthentification = $donneesSite['authentification'];
$etatFormulaire = $donneesSite['etat_formulaire'] ?? [];
$modaleAuthentification = $donneesSite['modale_authentification'] ?? $donneesSite['auth_modal'];
$modaleOuverte = !empty($etatFormulaire['ouverte']) ? 'true' : 'false';
$ongletActif = isset($etatFormulaire['onglet']) && $etatFormulaire['onglet'] === 'inscription' ? 'inscription' : 'connexion';
$erreursFormulaire = $etatFormulaire['erreurs'] ?? [];
$anciennesValeurs = $etatFormulaire['anciennes_valeurs'] ?? [];
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
                >
                    Créer un compte
                </button>
            </div>

            <?php if ($erreursFormulaire !== []): ?>
                <div class="auth-errors" role="alert">
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
                    <?= $ongletActif !== 'connexion' ? 'hidden' : '' ?>
                >
                    <input type="hidden" name="action" value="connexion">
                    <input type="hidden" name="jeton_csrf" value="<?= e($donneesSite['jeton_csrf']) ?>">
                    <input type="hidden" name="page_redirection" value="<?= e($pageCourante) ?>">

                    <label class="form-group">
                        <span>Email</span>
                        <input type="email" name="courriel" required autocomplete="email" value="<?= e((string) ($anciennesValeurs['courriel'] ?? '')) ?>">
                    </label>

                    <label class="form-group">
                        <span>Mot de passe</span>
                        <input type="password" name="mot_de_passe" required minlength="8" autocomplete="current-password">
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
                    <?= $ongletActif !== 'inscription' ? 'hidden' : '' ?>
                >
                    <input type="hidden" name="action" value="inscription">
                    <input type="hidden" name="jeton_csrf" value="<?= e($donneesSite['jeton_csrf']) ?>">
                    <input type="hidden" name="page_redirection" value="<?= e($pageCourante) ?>">

                    <div class="auth-grid">
                        <label class="form-group">
                            <span>Nom</span>
                            <input type="text" name="nom" required maxlength="100" autocomplete="family-name" value="<?= e((string) ($anciennesValeurs['nom'] ?? '')) ?>">
                        </label>

                        <label class="form-group">
                            <span>Prénom</span>
                            <input type="text" name="prenom" required maxlength="100" autocomplete="given-name" value="<?= e((string) ($anciennesValeurs['prenom'] ?? '')) ?>">
                        </label>
                    </div>

                    <label class="form-group">
                        <span>Date de naissance facultative</span>
                        <input type="date" name="date_naissance" autocomplete="bday" value="<?= e((string) ($anciennesValeurs['date_naissance'] ?? '')) ?>">
                    </label>

                    <label class="form-group">
                        <span>Email</span>
                        <input type="email" name="courriel" required autocomplete="email" value="<?= e((string) ($anciennesValeurs['courriel'] ?? '')) ?>">
                    </label>

                    <label class="form-group">
                        <span>Mot de passe</span>
                        <input type="password" name="mot_de_passe" required minlength="8" autocomplete="new-password">
                    </label>

                    <label class="form-group">
                        <span>Description du profil</span>
                        <textarea name="description_profil" rows="4" maxlength="1200"><?= e((string) ($anciennesValeurs['description_profil'] ?? '')) ?></textarea>
                    </label>

                    <button type="submit" class="button button-primary auth-submit">Créer le compte</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>


