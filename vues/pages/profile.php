<?php $authData = $siteData['auth']; ?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Profil</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<?php if (!$authData['is_authenticated']): ?>
    <section class="section-block reveal reveal-3">
        <div class="empty-state">
            <p class="card-tag">Connexion requise</p>
            <h2>Connecte-toi pour acceder a ton profil.</h2>
            <p>Le profil membre, la description personnelle et les preferences du compte sont accessibles apres connexion.</p>
            <button type="button" class="button button-primary" data-auth-open data-auth-tab="login">Connexion / inscription</button>
        </div>
    </section>
<?php else: ?>
    <?php $user = $authData['user']; ?>
    <section class="split-grid reveal reveal-3">
        <article class="panel">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Identite membre</p>
                <h2>Modifier les informations du profil.</h2>
                <p>La description de profil reste editable a tout moment depuis cette page.</p>
            </div>

            <form method="post" action="<?= e(route_url('profil')) ?>" class="article-form">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= e($siteData['csrf_token']) ?>">

                <div class="auth-grid">
                    <label class="form-group">
                        <span>Nom</span>
                        <input type="text" name="last_name" required maxlength="100" value="<?= e((string) ($user['last_name'] ?? '')) ?>">
                    </label>
                    <label class="form-group">
                        <span>Prenom</span>
                        <input type="text" name="first_name" required maxlength="100" value="<?= e((string) ($user['first_name'] ?? '')) ?>">
                    </label>
                </div>

                <label class="form-group">
                    <span>Date de naissance facultative</span>
                    <input type="date" name="birth_date" value="<?= e((string) ($user['birth_date'] ?? '')) ?>">
                </label>

                <label class="form-group">
                    <span>Email de connexion</span>
                    <input type="email" value="<?= e((string) ($user['email'] ?? '')) ?>" disabled>
                </label>

                <label class="form-group">
                    <span>Description du profil</span>
                    <textarea name="profile_description" rows="7" maxlength="1200"><?= e((string) ($user['profile_description'] ?? '')) ?></textarea>
                </label>

                <button type="submit" class="button button-primary">Mettre a jour mon profil</button>
            </form>
        </article>

        <article class="panel panel-contrast">
            <div class="section-head section-head--compact">
                <p class="eyebrow">Compte</p>
                <h2>Informations utiles sur la session.</h2>
                <p>Le compte repose sur une session PHP et un mot de passe hashé. Les publications d articles restent moderees.</p>
            </div>

            <ul class="bullet-list">
                <li>La connexion se fait avec votre email et votre mot de passe.</li>
                <li>La date de naissance reste facultative.</li>
                <li>La description de profil pourra etre affichee dans un futur espace membre.</li>
                <li>Le theme clair ou sombre reste memorise via un cookie de preference.</li>
            </ul>
        </article>
    </section>
<?php endif; ?>
