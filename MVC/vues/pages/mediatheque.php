<?php
$cartesMediatheque = $donneesSite['cartes_mediatheque'] ?? [];
$authData = $siteData['authentification'];
$publishedMedia = $siteData['published_media'] ?? [];
$myMedia = $siteData['my_media'] ?? [];
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Mediatheque</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Photos et videos</p>
        <h2>Des medias publics, valides avant publication.</h2>
        <p>Les photos et videos deposees par les adherents restent invisibles tant que l administrateur ne les a pas validees.</p>
    </div>

    <?php if ($publishedMedia === []): ?>
        <div class="card-grid card-grid--three">
            <?php foreach ($cartesMediatheque as $carteMedia): ?>
                <article class="info-card media-card">
                    <p class="card-tag"><?= e((string) ($carteMedia['type'] ?? 'Media')) ?></p>
                    <h3><?= e((string) ($carteMedia['titre'] ?? 'Media')) ?></h3>
                    <p><?= e((string) ($carteMedia['texte'] ?? '')) ?></p>
                    <p class="status-pill"><?= e((string) ($carteMedia['statut'] ?? 'En attente')) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card-grid card-grid--three">
            <?php foreach ($publishedMedia as $media): ?>
                <article class="info-card media-card">
                    <p class="card-tag"><?= e((string) ($media['type_media'] ?? 'media')) ?></p>
                    <h3><?= e((string) ($media['titre'] ?? 'Media')) ?></h3>
                    <p><?= e((string) ($media['description'] ?? '')) ?></p>
                    <p class="status-pill"><?= e((string) ($media['libelle_statut'] ?? 'Publie')) ?></p>

                    <?php if (($media['type_media'] ?? '') === 'video'): ?>
                        <video class="media-preview" controls preload="metadata">
                            <source src="<?= e((string) ($media['chemin_public'] ?? '')) ?>" type="<?= e((string) ($media['type_mime'] ?? 'video/mp4')) ?>">
                        </video>
                    <?php else: ?>
                        <img
                            class="media-preview"
                            src="<?= e((string) ($media['chemin_public'] ?? '')) ?>"
                            alt="<?= e((string) ($media['titre'] ?? 'Media du club')) ?>"
                            loading="lazy"
                        >
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Depot adherent</p>
            <h2>Envoyer une photo ou une video.</h2>
            <p>Le depot est reserve aux adherents du club. Chaque media passe ensuite en moderation.</p>
        </div>

        <?php if (!$authData['is_authenticated']): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag">Connexion requise</p>
                <h3>Connecte-toi pour utiliser l espace membre.</h3>
                <p>Les visiteurs peuvent consulter les medias publics mais ne peuvent pas envoyer de fichiers.</p>
                <button type="button" class="button button-primary" data-auth-open data-auth-tab="connexion">Connexion</button>
            </div>
        <?php elseif (!($authData['peut_soumettre_medias'] ?? false)): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag"><?= e((string) ($authData['role_label'] ?? 'Compte')) ?></p>
                <h3>Ce compte ne peut pas deposer de media.</h3>
                <p>L envoi de photos et videos est reserve aux adherents et a l administrateur.</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url_route('mediatheque')) ?>" class="article-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_media">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <label class="form-group">
                    <span>Titre</span>
                    <input type="text" name="media_title" maxlength="150" required>
                </label>

                <label class="form-group">
                    <span>Type</span>
                    <select name="media_type" required>
                        <option value="photo">Photo</option>
                        <option value="video">Video</option>
                    </select>
                </label>

                <label class="form-group">
                    <span>Description</span>
                    <textarea name="media_description" rows="4" maxlength="500"></textarea>
                </label>

                <label class="form-group">
                    <span>Fichier</span>
                    <input type="file" name="media_fichier" required accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov">
                </label>

                <p class="form-helper">Photos accepteees: JPG, PNG, WEBP, GIF jusqu a 8 Mo. Videos acceptees: MP4, WEBM, MOV jusqu a 50 Mo.</p>

                <button type="submit" class="button button-primary">Envoyer a la moderation</button>
            </form>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Mes envois</p>
            <h2>Suivi des medias de mon compte.</h2>
            <p>Chaque envoi garde son statut tant que le president administrateur ne le publie pas.</p>
        </div>

        <?php if (!$authData['is_authenticated']): ?>
            <div class="empty-state">
                <p class="card-tag">Visiteur</p>
                <h3>Les medias personnels apparaissent apres connexion.</h3>
                <p>Le suivi de moderation n est visible que dans l espace membre.</p>
            </div>
        <?php elseif ($myMedia === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucun media</p>
                <h3>Ton compte n a encore rien depose.</h3>
                <p>Des que tu enverras une photo ou une video, elle apparaitra ici.</p>
            </div>
        <?php else: ?>
            <div class="stack-list">
                <?php foreach ($myMedia as $media): ?>
                    <article class="schedule-item">
                        <p class="card-tag"><?= e((string) ($media['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($media['titre'] ?? 'Media')) ?></h3>
                        <p><?= e((string) ($media['description'] ?? '')) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
