<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : chiffres du club.blade.
 */

$membresBureau = is_array($siteData['membres_bureau'] ?? null) ? $siteData['membres_bureau'] : [];
?>
<section class="section-block reveal reveal-6" data-accueil-slot="chiffres_du_club">
    <div class="section-head">
        <p class="eyebrow">Bureau du club</p>
        <h2>Les membres du bureau des echecs.</h2>
        <p>Retrouve les responsables du club avec leur role, leur presentation et une photo quand elle est disponible.</p>
    </div>

    <div class="card-grid card-grid--three bureau-grid">
        <?php foreach ($membresBureau as $membreBureau): ?>
            <?php
            $photoMembre = (string) ($membreBureau['photo'] ?? '');
            $nomCompletMembre = (string) ($membreBureau['nom_complet'] ?? $membreBureau['full_name'] ?? '');
            $initialesMembre = strtoupper((string) mb_substr($nomCompletMembre !== '' ? $nomCompletMembre : 'Club', 0, 2));
            ?>
            <article class="info-card bureau-card">
                <div class="bureau-card-media">
                    <?php if ($photoMembre !== ''): ?>
                        <img
                            class="bureau-card-avatar"
                            src="<?= e($photoMembre) ?>"
                            alt="Photo de <?= e($nomCompletMembre) ?>"
                            loading="lazy"
                        >
                    <?php else: ?>
                        <div class="bureau-card-fallback" aria-hidden="true"><?= e($initialesMembre) ?></div>
                    <?php endif; ?>

                    <div class="bureau-card-heading">
                        <p class="card-tag"><?= e((string) ($membreBureau['role'] ?? 'Bureau')) ?></p>
                        <h3><?= e($nomCompletMembre) ?></h3>
                    </div>
                </div>

                <p><?= e((string) ($membreBureau['description'] ?? '')) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($itemsHorairesClub !== []): ?>
        <details id="emploi-du-temps-complet" class="schedule-full-card">
            <summary>
                <span>Consulter l'emploi du temps complet</span>
            </summary>
            <div class="schedule-full-content">
                <div class="section-head section-head--compact">
                    <p class="eyebrow">Emploi du temps</p>
                    <h3><?= e($libelleSaisonHoraires) ?></h3>
                    <?php if ($messageJourFerie !== ''): ?>
                        <p><strong>Jour ferie :</strong> <?= e($messageJourFerie) ?></p>
                    <?php endif; ?>
                </div>

                <div class="schedule-full-grid">
                    <?php foreach ($itemsHorairesClub as $horaire): ?>
                        <article class="schedule-full-item">
                            <p class="card-tag">
                                <?= e((string) ($horaire['day'] ?? '')) ?>
                                <?php if (!empty($horaire['is_holiday'])): ?>
                                    · Jour ferie
                                <?php endif; ?>
                            </p>
                            <h4><?= e((string) ($horaire['time'] ?? '')) ?></h4>
                            <p class="card-subtitle"><?= e((string) ($horaire['title'] ?? '')) ?></p>
                            <?php if (!empty($horaire['details_lines']) && is_array($horaire['details_lines'])): ?>
                                <ul>
                                    <?php foreach ($horaire['details_lines'] as $ligneDetail): ?>
                                        <li><?= e((string) $ligneDetail) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
    <?php endif; ?>
</section>
