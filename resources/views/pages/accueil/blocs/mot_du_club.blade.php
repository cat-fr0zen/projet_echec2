<article class="panel" data-accueil-slot="mot_du_club">
    <?php
    $blocMotClub = is_array($blocsAccueilParCode['mot_du_club'] ?? null) ? $blocsAccueilParCode['mot_du_club'] : [];
    $titreMotClub = trim((string) ($blocMotClub['titre_personnalise'] ?? ''));
    $contenuMotClub = trim((string) ($blocMotClub['contenu_personnalise'] ?? ''));

    if ($titreMotClub === '') {
        $titreMotClub = 'Présentation';
    }

    if ($contenuMotClub === '') {
        $contenuMotClub = "Bienvenue chez Les Cavaliers d'Hérouville, un club d'échecs pas comme les autres ! Notre mission ? Faire découvrir et partager la passion du jeu d'échecs à tous. Des 5 ans jusqu'à 105 ans. Débutants curieux ou pros de la stratégie. Convivialité, apprentissage, progression... le tout dans la bonne humeur ! Que vous vouliez apprendre, progresser ou simplement jouer pour le plaisir... Venez faire travailler vos neurones dans une ambiance chaleureuse et stimulante ! Rejoignez-nous et faites partie d'une communauté passionnée !";
    }
    ?>
    <div class="section-head section-head--compact">
        <h2><?= e($titreMotClub) ?></h2>
        <p><?= nl2br(e($contenuMotClub)) ?></p>
    </div>
</article>
