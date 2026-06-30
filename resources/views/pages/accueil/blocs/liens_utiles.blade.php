<?php
$blocLiensUtiles = is_array($blocsAccueilParCode['liens_utiles'] ?? null) ? $blocsAccueilParCode['liens_utiles'] : [];
$titreLiensUtiles = trim((string) ($blocLiensUtiles['titre_personnalise'] ?? ''));
$introLiensUtiles = trim((string) ($blocLiensUtiles['contenu_personnalise'] ?? ''));
$liensUtilesAccueil = is_array($siteData['accueil_liens_utiles'] ?? null) ? $siteData['accueil_liens_utiles'] : [];

if ($titreLiensUtiles === '') {
    $titreLiensUtiles = 'Liste de liens utiles';
}
?>
<article class="panel panel-contrast" data-accueil-slot="liens_utiles">
    <div class="section-head section-head--compact">
        <h2><?= e($titreLiensUtiles) ?></h2>
        <?php if ($introLiensUtiles !== ''): ?>
            <p><?= nl2br(e($introLiensUtiles)) ?></p>
        <?php endif; ?>
    </div>

    <ul class="bullet-list useful-links-list">
        <?php foreach ($liensUtilesAccueil as $lienUtileAccueil): ?>
            <?php
            $libelleLienUtile = trim((string) ($lienUtileAccueil['label'] ?? ''));
            $urlLienUtile = trim((string) ($lienUtileAccueil['url'] ?? ''));
            ?>
            <?php if ($libelleLienUtile === '' || $urlLienUtile === ''): ?>
                <?php continue; ?>
            <?php endif; ?>
            <li>
                <a
                    class="useful-link"
                    href="<?= e($urlLienUtile) ?>"
                    target="_blank"
                    rel="noopener noreferrer external"
                    referrerpolicy="no-referrer"
                >
                    <?= e($libelleLienUtile) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</article>
