<?php
/**
 * Vue: Le club.
 *
 * Présente les services du club et les rendez-vous réguliers.
 */
?>

<section class="page-banner reveal reveal-2">
    <p class="eyebrow">Le club</p>
    <h1><?= e($pageData['title']) ?></h1>
    <p><?= e($pageData['intro']) ?></p>
</section>

<section class="section-block reveal reveal-3">
    <div class="section-head">
        <p class="eyebrow">Services</p>
        <h2>Nos services</h2>
        <p>
            Les Cavaliers d'Hérouville proposent une large gamme d'activités autour du jeu d'échecs
            pour tous les âges et tous les niveaux, dans une ambiance conviviale et stimulante.
        </p>
    </div>

    <div class="card-grid card-grid--three">
        <article class="info-card">
            <p class="card-tag">Cours d'échecs</p>
            <h3>Une progression adaptée à chaque profil.</h3>
            <p>
                Des cours hebdomadaires pour enfants, adolescents et adultes, encadrés par des
                formateurs expérimentés, avec apprentissage des règles, perfectionnement stratégique,
                préparation à la compétition et accompagnement régulier.
            </p>
        </article>

        <article class="info-card">
            <p class="card-tag">Séances d'analyse</p>
            <h3>Comprendre ses parties pour mieux progresser.</h3>
            <p>
                Les séances d'analyse aident à identifier les points forts, corriger les erreurs,
                comprendre les moments clés d'une partie et développer une réflexion plus solide.
            </p>
        </article>

        <article class="info-card">
            <p class="card-tag">Tournois</p>
            <h3>Jouer en club, en local ou en officiel.</h3>
            <p>
                Le club organise des tournois internes et participe à des compétitions officielles
                FFE ainsi qu'à des tournois locaux ou régionaux, pour les membres motivés.
            </p>
        </article>

        <article class="info-card">
            <p class="card-tag">Stages</p>
            <h3>Des temps forts pendant les vacances.</h3>
            <p>
                Des stages ponctuels peuvent être proposés durant les congés scolaires pour découvrir,
                approfondir ou varier la pratique avec tournois amicaux, entraînements ciblés et animations.
            </p>
        </article>

        <article class="info-card">
            <p class="card-tag">Scolaire</p>
            <h3>Le jeu d'échecs comme outil d'apprentissage.</h3>
            <p>
                Les Cavaliers d'Hérouville interviennent dans les établissements scolaires pour faire
                découvrir les échecs et développer concentration, logique et esprit d'analyse.
            </p>
        </article>

        <article class="info-card">
            <p class="card-tag">Vie du club</p>
            <h3>Un club vivant, convivial et ouvert.</h3>
            <p>
                Journées ou soirées à thème, tournois ludiques, événements festifs et moments conviviaux
                renforcent la vie associative pour les adhérents et leurs familles.
            </p>
        </article>
    </div>
</section>

<section class="split-grid reveal reveal-4">
    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Rendez-vous</p>
            <h2>Les rendez-vous réguliers du club.</h2>
            <p>Retrouve ici les principaux temps de rencontre, d'animation et de pratique autour du club.</p>
        </div>

        <div class="stack-list">
            <div class="schedule-item">
                <div class="schedule-topline">
                    <span class="schedule-day">Vendredi</span>
                    <span class="schedule-slot">18h00 - 19h30</span>
                </div>
                <h3>Café des Images</h3>
                <p>Moment convivial autour des échecs à Hérouville Saint-Clair.</p>
            </div>

            <div class="schedule-item">
                <div class="schedule-topline">
                    <span class="schedule-day">Lundi soir</span>
                    <span class="schedule-slot">Résidence universitaire</span>
                </div>
                <h3>Salle LCR d'Hérouville</h3>
                <p>Rencontre informelle pour jouer, échanger et faire vivre la pratique du club.</p>
            </div>

            <div class="schedule-item">
                <div class="schedule-topline">
                    <span class="schedule-day">Animations</span>
                    <span class="schedule-slot">Selon calendrier</span>
                </div>
                <h3>Rue aux enfants et Escales Estivales</h3>
                <p>Présence du club à Hérouville Saint-Clair lors d'événements publics et familiaux.</p>
            </div>
        </div>
    </article>

    <article class="panel">
        <div class="section-head section-head--compact">
            <p class="eyebrow">Temps forts</p>
            <h2>Des activités variées toute l'année.</h2>
            <p>
                Au-delà des rendez-vous réguliers, le club propose aussi des stages, des événements festifs,
                des tournois déguisés et des actions ponctuelles pour rendre la pratique vivante et accessible.
            </p>
        </div>

        <ul class="bullet-list bullet-list--dark">
            <li>cours hebdomadaires pour débutants et joueurs confirmés</li>
            <li>analyse de parties pour progresser plus vite</li>
            <li>tournois amicaux, locaux et compétitions officielles</li>
            <li>stages pendant les vacances et animations découvertes</li>
            <li>interventions scolaires et actions publiques à Hérouville</li>
        </ul>
    </article>
</section>
