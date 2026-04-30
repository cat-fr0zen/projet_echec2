<?php

declare(strict_types=1);

final class ModeleSite
{
    public function obtenirDonneesSite(): array
    {
        $navigationPrincipale = [
            ['slug' => 'accueil', 'label' => 'Accueil'],
            ['slug' => 'guide', 'label' => 'Guide'],
            ['slug' => 'mediatheque', 'label' => 'Médiathèque'],
            ['slug' => 'articles', 'label' => 'Articles'],
            ['slug' => 'boutique', 'label' => 'Boutique'],
        ];

        $navigationSecondaire = [
            ['slug' => 'club', 'label' => 'Le club'],
            ['slug' => 'activites', 'label' => 'Activités'],
            ['slug' => 'contact', 'label' => 'Contact'],
        ];

        $statistiques = [
            $this->blocStatistique('01', 'Espace membre', "Connexion locale par email, profil personnel et réglages enregistrés."),
            $this->blocStatistique('02', 'Cookies encadrés', "Consentement obligatoire, préférence de thème et session membre."),
            $this->blocStatistique('03', 'Publication modérée', "Les articles, photos et vidéos restent en attente de validation."),
        ];

        $valeurs = [
            $this->blocTexte('Publication officielle', "Les informations sensibles ou institutionnelles ne sont publiées qu'après validation."),
            $this->blocTexte('Expérience membre', "Le site accueille des comptes membres, un profil éditable et un espace de rédaction."),
            $this->blocTexte('Conformité continue', "Mentions légales, confidentialité, cookies, droit à l'image et propriété intellectuelle sont explicités."),
        ];

        $planning = [
            $this->blocPlanning('Bloc 01', 'À publier', 'Identité associative', "Le nom officiel, le siège, les responsables et les informations publiques seront affichés ici."),
            $this->blocPlanning('Bloc 02', 'À publier', 'Organisation pratique', "Les horaires, le lieu de jeu, les groupes et les modalités d'accueil seront ajoutés ici."),
            $this->blocPlanning('Bloc 03', 'À publier', 'Coordonnées et documents', "Les contacts, les pièces utiles et les informations de communication seront centralisés ici."),
        ];

        $activites = [
            $this->carteSimple('Section', 'Public concerné', "Cette carte recevra les publics et catégories officiellement communiqués par l'association."),
            $this->carteSimple('Section', 'Formats de pratique', "Cours, jeu libre, animation ou accompagnement seront renseignés après validation associative."),
            $this->carteSimple('Section', 'Calendrier', "Le planning des activités sera ajouté ici lorsqu'il sera officiellement établi."),
            $this->carteSimple('Section', 'Documents utiles', "Bulletin d'adhésion, règlement intérieur et documents de référence pourront être centralisés ici."),
            $this->carteSimple('Section', 'Accès et inscription', "Les conditions d'inscription, d'adhésion et de participation seront affichées de façon claire."),
            $this->carteSimple('Section', 'Communication officielle', "Les annonces, actualités et mises à jour validées par l'association seront publiées dans ce cadre."),
        ];

        $etapesInscription = [
            "Créer un compte avec son nom, son prénom, son email et un mot de passe sécurisé.",
            "Mettre à jour son profil, sa description personnelle et ses préférences une fois connecté.",
            "Utiliser les espaces guide, articles, médiathèque ou boutique selon les contenus publiquement disponibles.",
        ];

        $pointsConformite = [
            "Le consentement cookies doit être accepté avant l'entrée sur le site.",
            "Les comptes membres reposent sur une session PHP, un mot de passe haché et des formulaires protégés.",
            "Les articles, images et vidéos proposés par les membres ne sont jamais publiés automatiquement.",
            "Le design, les contenus, les médias et les publications du site relèvent de la propriété intellectuelle.",
        ];

        $carrouselPieces = [
            $this->piece('Pion', '♙', "Le pion avance case par case, capture en diagonale et peut se promouvoir en fin de course."),
            $this->piece('Tour', '♖', "La tour se déplace en ligne droite sur les rangs et les colonnes et domine les couloirs ouverts."),
            $this->piece('Cavalier', '♘', "Le cavalier se déplace en L et peut sauter par-dessus les autres pièces."),
            $this->piece('Fou', '♗', "Le fou se déplace en diagonale et contrôle de longues trajectoires sur une seule couleur."),
            $this->piece('Reine', '♕', "La reine combine les mouvements de la tour et du fou et reste la pièce la plus mobile."),
            $this->piece('Roi', '♔', "Le roi avance d'une case autour de lui et sa sécurité décide de toute la partie."),
        ];

        $credits = [
            'auteur_site' => 'Matthéo Mullois',
            'publication_associative' => 'Jean Patrick JORON',
            'site_author' => 'Matthéo Mullois',
            'association_publisher' => 'Jean Patrick JORON',
        ];

        $registreCookies = [
            $this->cookie('PHPSESSID', 'Essentiel', "Maintenir la session des membres connectés et sécuriser l'accès au profil."),
            $this->cookie('site_consent', 'Essentiel', "Conserver la preuve du consentement obligatoire à la confidentialité et aux cookies."),
            $this->cookie('site_theme', 'Préférence', "Retenir le thème clair ou sombre choisi dans l'interface."),
        ];

        $documentsLegaux = [
            $this->documentLegal(
                'legal-notice',
                'Mentions légales',
                "Édition, publication, données, modération, propriété intellectuelle et responsabilités.",
                [
                    $this->sectionLegale('Édition et publication', [
                        "Conception et développement du site : Matthéo Mullois.",
                        "Publication associative et validation institutionnelle : Jean Patrick JORON.",
                        "Les informations publiques de l'association doivent être complétées et validées par les responsables habilités avant publication définitive.",
                    ]),
                    $this->sectionLegale('Espace membre et modération', [
                        "Les utilisateurs peuvent créer un compte membre via leur email et un mot de passe sécurisé.",
                        "Les articles, images et vidéos soumis depuis l'espace membre restent en attente de validation par un futur rôle administrateur avant publication publique.",
                    ]),
                    $this->sectionLegale("Propriété intellectuelle et droits d'exploitation", [
                        "La structure, le design, les textes, les médias, les scripts, les visuels, les logos et les documents sont protégés par le droit d'auteur et, le cas échéant, par le droit des marques.",
                        "Toute reproduction, adaptation, republication ou diffusion sans autorisation écrite préalable est interdite, hors exceptions légales.",
                    ]),
                    $this->sectionLegale("Images, vidéos et droit à l'image", [
                        "Toute photo ou vidéo destinée à la médiathèque doit disposer d'un fondement de diffusion, d'une autorisation adaptée ou d'un droit d'exploitation documenté.",
                        "L'association se réserve le droit de retirer immédiatement tout média en cas de doute sur les droits, l'autorisation ou la conformité juridique.",
                    ]),
                    $this->sectionLegale('Responsabilité', [
                        "Malgré le soin apporté à la publication, l'association et l'auteur du site ne garantissent pas l'absence totale d'erreur, d'omission ou d'interruption.",
                        "Les contenus externes accessibles par lien restent sous la responsabilité de leurs propres éditeurs.",
                    ]),
                ]
            ),
            $this->documentLegal(
                'privacy-policy',
                'Politique de confidentialité',
                "Données traitées, comptes membres, cookies, conservation et droits des personnes.",
                [
                    $this->sectionLegale('Données potentiellement traitées', [
                        "Nom, prénom, date de naissance facultative, email, mot de passe haché, description de profil et pseudo Chess.com facultatif saisis lors de l'inscription ou de la mise à jour du profil.",
                        "Métadonnées minimales nécessaires à la sécurité du service, au consentement, à la session membre, à la modération éditoriale et aux journaux serveurs.",
                        "Le cas échéant, informations liées aux médias, à leurs ayants droit, à leur durée de diffusion et aux autorisations de publication.",
                    ]),
                    $this->sectionLegale('Finalités du traitement', [
                        "Créer et maintenir un compte membre, permettre la connexion par email, l'édition du profil, la liaison facultative à un pseudo Chess.com public et la création d'articles en attente de modération.",
                        "Administrer le site, assurer la sécurité technique, la modération et conserver la preuve du consentement lorsque cela est nécessaire.",
                        "Préparer la gestion future d'une médiathèque, d'un espace boutique et d'une base Oracle structurée pour la maintenance long terme.",
                    ]),
                    $this->sectionLegale('Cookies, stockage et conservation', [
                        "Un consentement obligatoire de première entrée est présenté avant l'accès au contenu du site.",
                        "Le site utilise des cookies ou mécanismes similaires strictement limités à la session, au consentement et aux préférences d'affichage.",
                        "Les durées de conservation définitives devront être validées et documentées par l'association avant la mise en production finale.",
                    ]),
                    $this->sectionLegale('Droits des personnes', [
                        "Chaque personne dispose des droits d'accès, de rectification, d'effacement, de limitation, d'opposition et, selon les cas, de portabilité.",
                        "Les demandes devront être traitées par le contact officiel de l'association lorsqu'il sera publié.",
                    ]),
                ]
            ),
            $this->documentLegal(
                'terms-of-use',
                "Conditions d'utilisation",
                "Usage du site, espace membre, articles, médias, boutique et cadre associatif lié au jeu d'échecs.",
                [
                    $this->sectionLegale('Usage général', [
                        "L'utilisateur s'engage à consulter le site dans le respect de la loi, de l'ordre public et de l'objet associatif.",
                        "Toute tentative de détournement, extraction massive, nuisance technique, usurpation de compte ou publication malveillante est interdite.",
                    ]),
                    $this->sectionLegale('Comptes membres', [
                        "Chaque compte doit être créé avec un email valable et un mot de passe confidentiel choisi par l'utilisateur.",
                        "L'utilisateur reste responsable des informations qu'il saisit dans son profil, y compris le pseudo Chess.com éventuellement renseigné, et dans les contenus qu'il soumet.",
                    ]),
                    $this->sectionLegale('Articles, médias et boutique', [
                        "Les articles, médias du club, contenus de boutique et statistiques publiques liées à des services tiers peuvent être affichés, modifiés, refusés ou retirés selon les besoins de modération, de conformité ou de publication.",
                        "Aucune offre commerciale, publication ou diffusion de média ne vaut engagement définitif tant qu'elle n'a pas été publiée officiellement par l'association.",
                    ]),
                ]
            ),
        ];

        $consentement = [
            'nom_cookie' => 'site_consent',
            'titre' => "Validation obligatoire avant l'entrée",
            'introduction' => "Avant d'accéder au site, vous devez accepter la politique de confidentialité, les conditions d'utilisation et l'usage des cookies essentiels et de préférence.",
            'cases' => [
                "J'ai lu la politique de confidentialité, y compris les informations sur les comptes membres, les images, les vidéos et la connexion par email.",
                "J'accepte les cookies essentiels de session, de consentement et de préférence de thème.",
                "J'ai pris connaissance des mentions légales, de la modération des articles, des médias et de la propriété intellectuelle.",
            ],
            'bouton' => "Accepter et entrer sur le site",
            'cookie_name' => 'site_consent',
            'title' => "Validation obligatoire avant l'entrée",
            'intro' => "Avant d'accéder au site, vous devez accepter la politique de confidentialité, les conditions d'utilisation et l'usage des cookies essentiels et de préférence.",
            'checks' => [
                "J'ai lu la politique de confidentialité, y compris les informations sur les comptes membres, les images, les vidéos et la connexion par email.",
                "J'accepte les cookies essentiels de session, de consentement et de préférence de thème.",
                "J'ai pris connaissance des mentions légales, de la modération des articles, des médias et de la propriété intellectuelle.",
            ],
            'button' => "Accepter et entrer sur le site",
        ];

        $modaleAuthentification = [
            'title' => 'Connexion et inscription',
            'login_title' => 'Se connecter',
            'register_title' => 'Créer un compte',
        ];

        return [
            'brand' => "Association d'échecs",
            'ville' => 'Informations officielles en cours de validation',
            'city' => 'Informations officielles en cours de validation',
            'accroche' => "Cadre légal, espace membre, publication responsable et contenus validés par l'association.",
            'tagline' => "Cadre légal, espace membre, publication responsable et contenus validés par l'association.",
            'appel_action' => 'Connexion / inscription',
            'cta' => 'Connexion / inscription',
            'adresse' => 'Coordonnées postales à publier',
            'address' => 'Coordonnées postales à publier',
            'courriel' => 'Adresse de contact à publier',
            'email' => 'Adresse de contact à publier',
            'telephone' => 'Numéro de contact à publier',
            'phone' => 'Numéro de contact à publier',
            'navigation_principale' => $navigationPrincipale,
            'primary_nav' => $navigationPrincipale,
            'navigation_secondaire' => $navigationSecondaire,
            'secondary_nav' => $navigationSecondaire,
            'statistiques' => $statistiques,
            'stats' => $statistiques,
            'valeurs' => $valeurs,
            'values' => $valeurs,
            'planning' => $planning,
            'schedule' => $planning,
            'activites' => $activites,
            'activities' => $activites,
            'etapes_inscription' => $etapesInscription,
            'join_steps' => $etapesInscription,
            'points_conformite' => $pointsConformite,
            'compliance_points' => $pointsConformite,
            'carrousel_pieces' => $carrouselPieces,
            'piece_carousel' => $carrouselPieces,
            'credits' => $credits,
            'registre_cookies' => $registreCookies,
            'cookie_register' => $registreCookies,
            'documents_legaux' => $documentsLegaux,
            'legal_documents' => $documentsLegaux,
            'consentement' => $consentement,
            'consent' => $consentement,
            'modale_authentification' => $modaleAuthentification,
            'auth_modal' => $modaleAuthentification,
        ];
    }

    public function obtenirPages(): array
    {
        return [
            'accueil' => $this->page(
                'Accueil',
                'accueil.php',
                "Accueil, guide des pièces, connexion membre, cookies et cadre légal.",
                "Le jeu d'échecs, pièce par pièce, membre par membre.",
                "Ce site combine un espace membre local, un cadre de publication responsable, une gestion des cookies et des contenus accessibles après consentement.",
                "Connexion par email, thème clair ou sombre, menu burger et articles en attente de modération."
            ),
            'guide' => $this->page(
                'Guide',
                'guide.php',
                'Guides de stratégie, principes de jeu et cartes de progression.',
                intro: "Des cartes simples pour rappeler les principes stratégiques les plus utiles à un joueur de club."
            ),
            'mediatheque' => $this->page(
                'Médiathèque',
                'mediatheque.php',
                'Galeries photos, vidéos et espaces médiathèque du club.',
                intro: "Cette page regroupera les photos et vidéos du club dès qu'elles seront publiées officiellement."
            ),
            'articles' => $this->page(
                'Articles',
                'articles.php',
                'Articles publics, soumissions membres et modération future.',
                intro: "Les membres peuvent proposer des articles, mais leur publication publique reste soumise à validation."
            ),
            'boutique' => $this->page(
                'Boutique',
                'boutique.php',
                'Espace boutique, catalogue et ventes à venir.',
                intro: "Cette page accueillera le catalogue de produits du club lorsqu'une publication officielle sera prête."
            ),
            'merch' => $this->page(
                'Boutique',
                'boutique.php',
                'Espace boutique, catalogue et ventes à venir.',
                intro: "Cette page accueillera le catalogue de produits du club lorsqu'une publication officielle sera prête."
            ),
            'club' => $this->page(
                'Le club',
                'club.php',
                'Présentation institutionnelle et cadre de publication du club.',
                intro: "Cette page est réservée à la présentation officielle du club, de ses responsables et de son fonctionnement validé."
            ),
            'activites' => $this->page(
                'Activités',
                'activites.php',
                'Organisation des activités, documents utiles et publication officielle.',
                intro: "Cette page accueillera les activités, les documents et le calendrier uniquement lorsqu'ils auront été officiellement validés."
            ),
            'contact' => $this->page(
                'Contact',
                'contact.php',
                'Contact, crédits du site, publication associative et cadre de confidentialité.',
                intro: "Cette page centralise les responsables nommés, le cadre de publication et les informations de contact lorsqu'elles sont officiellement disponibles."
            ),
            'profil' => $this->page(
                'Profil',
                'profil.php',
                'Profil membre, description éditable, pseudo Chess.com facultatif et informations personnelles.',
                intro: "Cette page permet au membre connecté de modifier son profil, sa description, son pseudo Chess.com facultatif et ses informations personnelles."
            ),
            'parametres' => $this->page(
                'Paramètres',
                'parametres.php',
                'Paramètres, thème, cookies et informations de session.',
                intro: "Cette page regroupe les préférences du site, les informations sur les cookies et les réglages d'interface."
            ),
        ];
    }

    public function obtenirCartesGuide(): array
    {
        return [
            $this->carteSimple('Ouverture', 'Contrôler le centre', "Occuper ou attaquer les cases centrales donne plus d'espace et facilite le développement."),
            $this->carteSimple('Développement', 'Sortir les pièces vite', "Activer rapidement cavaliers et fous évite de perdre du temps et prépare le roque."),
            $this->carteSimple('Sécurité', "Mettre le roi à l'abri", "Roquer tôt permet souvent de connecter les tours et de réduire les menaces centrales."),
            $this->carteSimple('Tactique', 'Chercher les fourchettes', "Les fourchettes, en particulier avec le cavalier, peuvent faire gagner du matériel rapidement."),
            $this->carteSimple('Milieu de jeu', 'Améliorer la pièce la moins active', "Quand aucun coup tactique n'apparaît, améliorer sa pièce la moins bien placée reste un excellent réflexe."),
            $this->carteSimple('Finale', 'Activer le roi', "En finale, le roi devient une pièce forte et doit souvent participer activement."),
        ];
    }

    public function obtenirCartesMediatheque(): array
    {
        return [
            $this->carteStatut('Galerie photo', 'Albums du club', "Aucun album public n'est encore publié. Le cadre est prêt pour accueillir les prochains reportages.", 'En attente'),
            $this->carteStatut('Vidéo', 'Captations et temps forts', "Cette zone accueillera les vidéos du club, interviews, animations et résumés d'événements.", 'En attente'),
            $this->carteStatut('Archives', 'Mémoires visuelles', "Les archives photo ou vidéo resteront publiées seulement après vérification des droits et autorisations.", 'Contrôle des droits'),
        ];
    }

    public function obtenirCartesBoutique(): array
    {
        return [
            $this->carteStatut('Textile', 'Collection club', "Espace prévu pour les textiles officiels, à publier uniquement lorsqu'une offre aura été validée.", 'Bientôt disponible'),
            $this->carteStatut('Accessoires', 'Objets utilitaires', "Le site pourra présenter accessoires, petits objets et matériel dès que le catalogue sera confirmé.", 'Bientôt disponible'),
            $this->carteStatut('Matériel', 'Sélection de produits', "Les références, stocks, tarifs et conditions de vente devront être officiellement publiés avant toute diffusion.", 'Publication encadrée'),
        ];
    }

    public function obtenirDonneesIntrouvable(): array
    {
        return [
            'titre' => 'Page introuvable',
            'title' => 'Page introuvable',
            'description_meta' => "La page demandée n'existe pas ou n'est plus disponible.",
            'meta_description' => "La page demandée n'existe pas ou n'est plus disponible.",
            'message' => "La page demandée n'existe pas. Le plus simple est de revenir à l'accueil puis de reprendre la navigation.",
        ];
    }

    private function page(
        string $titre,
        string $vue,
        string $descriptionMeta,
        string $titreHero = '',
        string $texteHero = '',
        string $noteHero = '',
        string $intro = ''
    ): array {
        return [
            'titre' => $titre,
            'title' => $titre,
            'vue' => $vue,
            'view' => $vue,
            'description_meta' => $descriptionMeta,
            'meta_description' => $descriptionMeta,
            'titre_hero' => $titreHero,
            'hero_title' => $titreHero,
            'texte_hero' => $texteHero,
            'hero_text' => $texteHero,
            'note_hero' => $noteHero,
            'hero_note' => $noteHero,
            'intro' => $intro,
        ];
    }

    private function blocStatistique(string $valeur, string $libelle, string $texte): array
    {
        return [
            'valeur' => $valeur,
            'value' => $valeur,
            'libelle' => $libelle,
            'label' => $libelle,
            'texte' => $texte,
            'text' => $texte,
        ];
    }

    private function blocTexte(string $titre, string $texte): array
    {
        return [
            'titre' => $titre,
            'title' => $titre,
            'texte' => $texte,
            'text' => $texte,
        ];
    }

    private function blocPlanning(string $jour, string $creneau, string $titre, string $texte): array
    {
        return [
            'jour' => $jour,
            'day' => $jour,
            'creneau' => $creneau,
            'slot' => $creneau,
            'titre' => $titre,
            'title' => $titre,
            'texte' => $texte,
            'text' => $texte,
        ];
    }

    private function carteSimple(string $tag, string $titre, string $texte): array
    {
        return [
            'tag' => $tag,
            'titre' => $titre,
            'title' => $titre,
            'texte' => $texte,
            'text' => $texte,
        ];
    }

    private function carteStatut(string $type, string $titre, string $texte, string $statut): array
    {
        return [
            'type' => $type,
            'titre' => $titre,
            'title' => $titre,
            'texte' => $texte,
            'text' => $texte,
            'statut' => $statut,
            'status' => $statut,
        ];
    }

    private function piece(string $nom, string $glyphe, string $role): array
    {
        return [
            'nom' => $nom,
            'name' => $nom,
            'glyphe' => $glyphe,
            'glyph' => $glyphe,
            'role' => $role,
        ];
    }

    private function cookie(string $nom, string $type, string $finalite): array
    {
        return [
            'nom' => $nom,
            'name' => $nom,
            'type' => $type,
            'finalite' => $finalite,
            'purpose' => $finalite,
        ];
    }

    private function documentLegal(string $id, string $titre, string $resume, array $sections): array
    {
        return [
            'id' => $id,
            'titre' => $titre,
            'title' => $titre,
            'resume' => $resume,
            'summary' => $resume,
            'sections' => $sections,
        ];
    }

    private function sectionLegale(string $titre, array $elements): array
    {
        return [
            'titre' => $titre,
            'title' => $titre,
            'elements' => $elements,
            'items' => $elements,
        ];
    }
}
