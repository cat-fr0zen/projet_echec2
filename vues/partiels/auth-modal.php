<?php
$authData = $siteData['auth'];
$formState = $siteData['form_state'] ?? [];
$authModal = $siteData['auth_modal'];
$formIsOpen = !empty($formState['is_open']) ? 'true' : 'false';
$activeTab = isset($formState['tab']) && $formState['tab'] === 'register' ? 'register' : 'login';
$formErrors = $formState['errors'] ?? [];
$formOld = $formState['old'] ?? [];
?>

<?php if (!$authData['is_authenticated']): ?>
    <div
        class="auth-modal"
        data-auth-modal
        data-auth-open-state="<?= e($formIsOpen) ?>"
        data-auth-tab="<?= e($activeTab) ?>"
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="auth-modal-title"
        aria-describedby="auth-modal-description"
    >
        <div class="auth-modal-panel">
            <button type="button" class="auth-close" data-auth-close aria-label="Fermer la fenetre">×</button>
            <p class="eyebrow">Espace membre</p>
            <h2 id="auth-modal-title"><?= e($authModal['title']) ?></h2>
            <p id="auth-modal-description" class="auth-modal-description">
                Connecte-toi avec ton email ou cree ton compte membre pour acceder au profil, aux articles et aux reglages.
            </p>

            <div class="auth-tab-row" role="tablist" aria-label="Connexion ou inscription">
                <button
                    type="button"
                    class="auth-tab-button"
                    data-auth-tab-trigger="login"
                    role="tab"
                    id="auth-tab-login"
                    aria-controls="auth-panel-login"
                    aria-selected="<?= $activeTab === 'login' ? 'true' : 'false' ?>"
                >
                    <?= e($authModal['login_title']) ?>
                </button>
                <button
                    type="button"
                    class="auth-tab-button"
                    data-auth-tab-trigger="register"
                    role="tab"
                    id="auth-tab-register"
                    aria-controls="auth-panel-register"
                    aria-selected="<?= $activeTab === 'register' ? 'true' : 'false' ?>"
                >
                    <?= e($authModal['register_title']) ?>
                </button>
            </div>

            <?php if ($formErrors !== []): ?>
                <div class="auth-errors" role="alert">
                    <?php foreach ($formErrors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="auth-panels">
                <form
                    method="post"
                    action="<?= e(route_url($currentPage)) ?>"
                    class="auth-form"
                    data-auth-panel="login"
                    id="auth-panel-login"
                    role="tabpanel"
                    aria-labelledby="auth-tab-login"
                    <?= $activeTab !== 'login' ? 'hidden' : '' ?>
                >
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?= e($siteData['csrf_token']) ?>">
                    <input type="hidden" name="redirect_page" value="<?= e($currentPage) ?>">

                    <label class="form-group">
                        <span>Email</span>
                        <input type="email" name="email" required autocomplete="email" value="<?= e((string) ($formOld['email'] ?? '')) ?>">
                    </label>

                    <label class="form-group">
                        <span>Mot de passe</span>
                        <input type="password" name="password" required minlength="8" autocomplete="current-password">
                    </label>

                    <button type="submit" class="button button-primary auth-submit">Se connecter</button>
                </form>

                <form
                    method="post"
                    action="<?= e(route_url($currentPage)) ?>"
                    class="auth-form auth-form--register"
                    data-auth-panel="register"
                    id="auth-panel-register"
                    role="tabpanel"
                    aria-labelledby="auth-tab-register"
                    <?= $activeTab !== 'register' ? 'hidden' : '' ?>
                >
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?= e($siteData['csrf_token']) ?>">
                    <input type="hidden" name="redirect_page" value="<?= e($currentPage) ?>">

                    <div class="auth-grid">
                        <label class="form-group">
                            <span>Nom</span>
                            <input type="text" name="last_name" required maxlength="100" autocomplete="family-name" value="<?= e((string) ($formOld['last_name'] ?? '')) ?>">
                        </label>

                        <label class="form-group">
                            <span>Prenom</span>
                            <input type="text" name="first_name" required maxlength="100" autocomplete="given-name" value="<?= e((string) ($formOld['first_name'] ?? '')) ?>">
                        </label>
                    </div>

                    <label class="form-group">
                        <span>Date de naissance facultative</span>
                        <input type="date" name="birth_date" autocomplete="bday" value="<?= e((string) ($formOld['birth_date'] ?? '')) ?>">
                    </label>

                    <label class="form-group">
                        <span>Email</span>
                        <input type="email" name="email" required autocomplete="email" value="<?= e((string) ($formOld['email'] ?? '')) ?>">
                    </label>

                    <label class="form-group">
                        <span>Mot de passe</span>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    </label>

                    <label class="form-group">
                        <span>Description du profil</span>
                        <textarea name="profile_description" rows="4" maxlength="1200"><?= e((string) ($formOld['profile_description'] ?? '')) ?></textarea>
                    </label>

                    <button type="submit" class="button button-primary auth-submit">Creer le compte</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
