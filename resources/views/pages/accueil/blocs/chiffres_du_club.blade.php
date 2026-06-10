<section class="section-block reveal reveal-6" data-accueil-slot="chiffres_du_club">
    <div class="section-head">
        <p class="eyebrow">Chiffres du club</p>
        <h2>Les informations essentielles en un coup d'œil.</h2>
        <p>Les cartes résumées restent faciles à lire tout en gardant le style du site.</p>
    </div>

    <div class="card-grid card-grid--three">
        <?php foreach ($stats as $stat): ?>
            <article class="info-card">
                <p class="metric-value"><?= e($stat['value']) ?></p>
                <h3><?= e($stat['label']) ?></h3>
                <p><?= e($stat['text']) ?></p>
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
                        <p><strong>Jour férié :</strong> <?= e($messageJourFerie) ?></p>
                    <?php endif; ?>
                </div>

                <div class="schedule-full-grid">
                    <?php foreach ($itemsHorairesClub as $horaire): ?>
                        <article class="schedule-full-item">
                            <p class="card-tag">
                                <?= e((string) ($horaire['day'] ?? '')) ?>
                                <?php if (!empty($horaire['is_holiday'])): ?>
                                    · Jour férié
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
