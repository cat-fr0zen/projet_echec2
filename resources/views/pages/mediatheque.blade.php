<?php
/**
 * Vue: Médiathèque.
 *
 * Affiche:
 * - galerie publique des medias valides
 * - espace "mes medias" pour adherents (depots + suivi)
 *
 * Variables attendues:
 * - $donneesSite['cartes_mediatheque']
 * - $siteData['authentification'], listes publiees + personnelles
 */
$cartesMediatheque = $donneesSite['cartes_mediatheque'] ?? [];
$authData = $siteData['authentification'];
$publishedMedia = $siteData['published_media'] ?? [];
$mediaAlbums = $siteData['albums_medias'] ?? [];
$myMedia = $siteData['my_media'] ?? [];
$myMediaAlbums = $siteData['my_media_albums'] ?? [];
$myAlbumEligibleMedia = array_values(array_filter(
    $myMedia,
    static fn (array $media): bool => (string) ($media['statut'] ?? '') !== 'refuse'
));
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Médiathèque</p>
    <h1><?= e($donneesPage['titre']) ?></h1>
    <p><?= e($donneesPage['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Photos et vidéos</p>
        <h2>Des médias publics, validés avant publication.</h2>
        <p>Les photos et vidéos déposées par les adhérents restent invisibles tant que l'administrateur ne les a pas validées.</p>
    </div>

    <?php if ($mediaAlbums !== []): ?>
        <div class="stack-list" style="margin-bottom: 1.5rem;">
            <?php foreach ($mediaAlbums as $album): ?>
                <article class="panel">
                    <p class="card-tag">Album • <?= e((string) ($album['nombre_medias'] ?? 0)) ?> médias</p>
                    <h3><?= e((string) ($album['titre'] ?? 'Album')) ?></h3>
                    <p><?= e((string) ($album['description'] ?? '')) ?></p>

                    <div class="card-grid card-grid--three">
                        <?php foreach (($album['medias'] ?? []) as $media): ?>
                            <article class="info-card media-card media-card--clickable">
                                <button
                                    type="button"
                                    class="media-card-trigger"
                                    data-media-lightbox-trigger
                                    data-media-src="<?= e((string) ($media['chemin_public'] ?? '')) ?>"
                                    data-media-type="<?= e((string) ($media['type_media'] ?? 'photo')) ?>"
                                    data-media-mime="<?= e((string) ($media['type_mime'] ?? '')) ?>"
                                    data-media-label="<?= e((string) ($album['titre'] ?? 'Album du club')) ?>"
                                    aria-label="Voir ce média en plus grand"
                                >
                                    <?php if (($media['type_media'] ?? '') === 'video'): ?>
                                        <video class="media-preview" preload="metadata" muted playsinline>
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
                                </button>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

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
                <article class="info-card media-card media-card--clickable">
                    <button
                        type="button"
                        class="media-card-trigger"
                        data-media-lightbox-trigger
                        data-media-src="<?= e((string) ($media['chemin_public'] ?? '')) ?>"
                        data-media-type="<?= e((string) ($media['type_media'] ?? 'photo')) ?>"
                        data-media-mime="<?= e((string) ($media['type_mime'] ?? '')) ?>"
                        data-media-label="<?= e((string) ($media['titre'] ?? 'Média du club')) ?>"
                        aria-label="Voir ce média en plus grand"
                    >
                        <?php if (($media['type_media'] ?? '') === 'video'): ?>
                            <video class="media-preview" preload="metadata" muted playsinline>
                                <source src="<?= e((string) ($media['chemin_public'] ?? '')) ?>" type="<?= e((string) ($media['type_mime'] ?? 'video/mp4')) ?>">
                            </video>
                        <?php else: ?>
                            <img
                                class="media-preview"
                                src="<?= e((string) ($media['chemin_public'] ?? '')) ?>"
                                alt="<?= e((string) ($media['titre'] ?? 'Média du club')) ?>"
                                loading="lazy"
                            >
                        <?php endif; ?>
                    </button>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel panel-contrast">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Dépôt adhérent</p>
            <h2>Envoyer une photo ou une vidéo.</h2>
            <p>Le dépôt est réservé aux adhérents du club. Chaque média passe ensuite en modération.</p>
        </div>

        <?php if (!$authData['is_authenticated']): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag">Connexion requise</p>
                <h3>Connecte-toi pour utiliser l'espace membre.</h3>
                <p>Les visiteurs peuvent consulter les médias publics mais ne peuvent pas envoyer de fichiers.</p>
                <button type="button" class="button button-primary" data-auth-open data-auth-tab="connexion">Connexion</button>
            </div>
        <?php elseif (!($authData['peut_soumettre_medias'] ?? false)): ?>
            <div class="empty-state empty-state--contrast">
                <p class="card-tag"><?= e((string) ($authData['role_label'] ?? 'Compte')) ?></p>
                <h3>Ce compte ne peut pas déposer de média.</h3>
                <p>L'envoi de photos et vidéos est réservé aux adhérents et à l'administrateur.</p>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url_route('mediatheque')) ?>" class="article-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_media">
                <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                <label class="form-group">
                    <span>Titre</span>
                    <input type="text" name="media_title" maxlength="150" required>
                </label>

                <label class="form-group">
                    <span>Type</span>
                    <select name="media_type" required>
                        <option value="photo">Photo</option>
                        <option value="video">Vidéo</option>
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

                <p class="form-helper">Photos acceptées : JPG, PNG, WEBP, GIF jusqu’à 8 Mo. Vidéos acceptées : MP4, WEBM, MOV jusqu’à 50 Mo.</p>

                <button type="submit" class="button button-primary">Envoyer à la modération</button>
            </form>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid rgba(120, 94, 52, 0.16);">

            <div class="section-head section-head--compact">
                <p class="eyebrow">Album</p>
                <h2>Créer un album à faire valider.</h2>
                <p>Tu peux regrouper plusieurs médias de ton compte dans un album qui partira lui aussi en modération.</p>
            </div>

            <?php if ($myAlbumEligibleMedia === []): ?>
                <div class="empty-state empty-state--contrast">
                    <p class="card-tag">Aucun média</p>
                    <h3>Ajoute d'abord des photos ou vidéos.</h3>
                    <p>Un album ne peut contenir que des médias déjà envoyés depuis ton compte.</p>
                </div>
            <?php else: ?>
                <form method="post" action="<?= e(url_route('mediatheque')) ?>" class="article-form">
                    <input type="hidden" name="action" value="submit_media_album">
                    <input type="hidden" name="_token" value="<?= e($siteData['jeton_csrf']) ?>">
                    <input type="hidden" name="jeton_csrf" value="<?= e($siteData['jeton_csrf']) ?>">

                    <label class="form-group">
                        <span>Titre de l'album</span>
                        <input type="text" name="titre_album_media" maxlength="150" required>
                    </label>

                    <label class="form-group">
                        <span>Description</span>
                        <textarea name="description_album_media" rows="3" maxlength="500"></textarea>
                    </label>

                    <label class="form-group">
                        <span>Médias à inclure</span>
                        <div class="stack-list">
                            <?php foreach ($myAlbumEligibleMedia as $media): ?>
                                <label class="schedule-item">
                                    <span class="checkbox-inline">
                                        <input type="checkbox" name="album_media_ids[]" value="<?= e((string) ($media['identifiant'] ?? '')) ?>">
                                        <span><?= e((string) ($media['titre'] ?? 'Média')) ?> • <?= e((string) ($media['libelle_statut'] ?? 'En attente')) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </label>

                    <button type="submit" class="button button-secondary">Envoyer l'album à la modération</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Mes envois</p>
            <h2>Suivi des médias et albums de mon compte.</h2>
            <p>Chaque envoi garde son statut tant que le président administrateur ne le publie pas.</p>
        </div>

        <?php if (!$authData['is_authenticated']): ?>
            <div class="empty-state">
                <p class="card-tag">Visiteur</p>
                <h3>Les médias personnels apparaissent après connexion.</h3>
                <p>Le suivi de modération n'est visible que dans l'espace membre.</p>
            </div>
        <?php elseif ($myMedia === [] && $myMediaAlbums === []): ?>
            <div class="empty-state">
                <p class="card-tag">Aucun média</p>
                <h3>Ton compte n'a encore rien déposé.</h3>
                <p>Dès que tu enverras une photo, une vidéo ou un album, cela apparaîtra ici.</p>
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

                <?php foreach ($myMediaAlbums as $album): ?>
                    <article class="schedule-item">
                        <p class="card-tag">Album • <?= e((string) ($album['libelle_statut'] ?? 'En attente')) ?></p>
                        <h3><?= e((string) ($album['titre'] ?? 'Album')) ?></h3>
                        <p><?= e((string) ($album['description'] ?? '')) ?></p>
                        <p><?= e((string) ($album['nombre_medias'] ?? 0)) ?> média(x)</p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
