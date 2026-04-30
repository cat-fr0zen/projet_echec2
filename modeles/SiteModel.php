<?php

declare(strict_types=1);

final class SiteModel
{
    public function getSiteData(): array
    {
        return [
            'brand' => "Association d'échecs",
            'city' => "Informations officielles en cours de validation",
            'tagline' => "Cadre légal, espace membre, publication responsable et contenus validés par l'association.",
            'cta' => "Connexion / inscription",
            'address' => "Coordonnées postales à publier",
            'email' => "Adresse de contact à publier",
            'phone' => "Numéro de contact à publier",
            'primary_nav' => [
                ['slug' => 'accueil', 'label' => 'Accueil'],
                ['slug' => 'guide', 'label' => 'Guide'],
                ['slug' => 'mediatheque', 'label' => 'Médiathèque'],
                ['slug' => 'articles', 'label' => 'Articles'],
                ['slug' => 'merch', 'label' => 'Merch'],
            ],
            'secondary_nav' => [
                ['slug' => 'club', 'label' => 'Le club'],
                ['slug' => 'activites', 'label' => 'Activités'],
                ['slug' => 'contact', 'label' => 'Contact'],
            ],
            'stats' => [
                [
                    'value' => '01',
                    'label' => 'Espace membre',
                    'text' => "Connexion locale par email, profil personnel et modifications du profil depuis un espace dédié.",
                ],
                [
                    'value' => '02',
                    'label' => 'Cookies encadrés',
                    'text' => "Consentement obligatoire, cookie de préférence visuelle et cookie de session pour les utilisateurs connectés.",
                ],
                [
                    'value' => '03',
                    'label' => 'Publication modérée',
                    'text' => "Les articles, photos et vidéos proposés restent en attente de validation selon les droits et les autorisations disponibles.",
                ],
            ],
            'values' => [
                [
                    'title' => 'Publication officielle',
                    'text' => "Les zones publiques restent disponibles, mais les informations sensibles ou institutionnelles sont publiées seulement après validation.",
                ],
                [
                    'title' => 'Expérience membre',
                    'text' => "Le site accueille des comptes membres, un profil éditable, un espace de rédaction et des réglages persistants.",
                ],
                [
                    'title' => 'Conformité continue',
                    'text' => "Mentions légales, politique de confidentialité, cookies, droit à l'image et propriété intellectuelle sont explicités.",
                ],
            ],
            'schedule' => [
                [
                    'day' => 'Bloc 01',
                    'slot' => 'À publier',
                    'title' => 'Identité associative',
                    'text' => "Le nom officiel, le siège, les responsables et les informations publiques seront affichés ici.",
                ],
                [
                    'day' => 'Bloc 02',
                    'slot' => 'À publier',
                    'title' => 'Organisation pratique',
                    'text' => "Les horaires, le lieu de jeu, les groupes et les modalités d'accueil seront ajoutés ici.",
                ],
                [
                    'day' => 'Bloc 03',
                    'slot' => 'À publier',
                    'title' => 'Coordonnées et documents',
                    'text' => "Les contacts, les pièces utiles et les informations de communication seront centralisés ici.",
                ],
            ],
            'activities' => [
                [
                    'tag' => 'Section',
                    'title' => 'Public concerné',
                    'text' => "Cette carte recevra les publics et catégories officiellement communiqués par l'association.",
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Formats de pratique',
                    'text' => "Cours, jeu libre, animation ou accompagnement seront renseignés après validation associative.",
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Calendrier',
                    'text' => "Le planning des activités sera ajouté ici lorsqu'il sera officiellement établi.",
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Documents utiles',
                    'text' => "Bulletin d'adhésion, règlement intérieur et documents de référence pourront être centralisés ici.",
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Accès et inscription',
                    'text' => "Les conditions d'inscription, d'adhésion et de participation seront affichées de façon claire.",
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Communication officielle',
                    'text' => "Les annonces, actualités et mises à jour validées par l'association seront publiées dans ce cadre.",
                ],
            ],
            'join_steps' => [
                "Créer un compte avec son nom, son prénom, son email et un mot de passe sécurisé.",
                "Mettre à jour son profil, sa description personnelle et ses préférences une fois connecté.",
                "Utiliser les espaces guide, articles, médiathèque ou merch selon les contenus publiquement disponibles.",
            ],
            'compliance_points' => [
                "Le consentement cookies doit être accepté avant l'entrée sur le site.",
                "Les comptes membres reposent sur une session PHP, un mot de passe hashé et des formulaires protégés.",
                "Les articles, images et vidéos proposés par les membres ne sont jamais publiés automatiquement.",
                "Le design, les contenus, les médias et les publications du site relèvent de la propriété intellectuelle.",
            ],
            'piece_carousel' => [
                [
                    'name' => 'Pion',
                    'glyph' => '♙',
                    'role' => "Le pion avance case par case, capture en diagonale et peut se promouvoir en fin de course.",
                ],
                [
                    'name' => 'Tour',
                    'glyph' => '♖',
                    'role' => "La tour se déplace en ligne droite sur les rangs et les colonnes et domine les couloirs ouverts.",
                ],
                [
                    'name' => 'Cavalier',
                    'glyph' => '♘',
                    'role' => "Le cavalier se déplace en L et peut sauter par-dessus les autres pièces.",
                ],
                [
                    'name' => 'Fou',
                    'glyph' => '♗',
                    'role' => "Le fou se déplace en diagonale et contrôle de longues trajectoires sur une seule couleur.",
                ],
                [
                    'name' => 'Reine',
                    'glyph' => '♕',
                    'role' => "La reine combine les mouvements de la tour et du fou et reste la pièce la plus mobile.",
                ],
                [
                    'name' => 'Roi',
                    'glyph' => '♔',
                    'role' => "Le roi avance d'une case autour de lui et sa sécurité décide de toute la partie.",
                ],
            ],
            'credits' => [
                'site_author' => 'Matthéo Mullois',
                'association_publisher' => 'Jean Patrick JORON',
            ],
            'cookie_register' => [
                [
                    'name' => 'PHPSESSID',
                    'type' => 'Essentiel',
                    'purpose' => "Maintenir la session des membres connectés et sécuriser l'accès au profil.",
                ],
                [
                    'name' => 'site_consent',
                    'type' => 'Essentiel',
                    'purpose' => "Conserver la preuve du consentement obligatoire à la confidentialité et aux cookies.",
                ],
                [
                    'name' => 'site_theme',
                    'type' => 'Préférence',
                    'purpose' => "Retenir le thème clair ou sombre choisi dans l'interface.",
                ],
            ],
            'legal_documents' => [
                [
                    'id' => 'legal-notice',
                    'title' => 'Mentions légales',
                    'summary' => "Édition, publication, données, modération, propriété intellectuelle et responsabilités.",
                    'sections' => [
                        [
                            'title' => 'Édition et publication',
                            'items' => [
                                "Conception et développement du site : Matthéo Mullois.",
                                "Publication associative et validation institutionnelle : Jean Patrick JORON.",
                                "Les informations publiques de l'association doivent être complétées et validées par les responsables habilités avant publication définitive.",
                            ],
                        ],
                        [
                            'title' => 'Espace membre et modération',
                            'items' => [
                                "Les utilisateurs peuvent créer un compte membre via leur email et un mot de passe sécurisé.",
                                "Les articles, images et vidéos soumis depuis l'espace membre restent en attente de validation par un futur rôle administrateur avant publication publique.",
                            ],
                        ],
                        [
                            'title' => "Propriété intellectuelle et droits d'exploitation",
                            'items' => [
                                "La structure, le design, les textes, les médias, les scripts, les visuels, les logos et les documents sont protégés par le droit d'auteur et, le cas échéant, par le droit des marques.",
                                "Toute reproduction, adaptation, republication ou diffusion sans autorisation écrite préalable est interdite, hors exceptions légales.",
                            ],
                        ],
                        [
                            'title' => "Images, vidéos et droit à l'image",
                            'items' => [
                                "Toute photo ou vidéo destinée à la médiathèque doit disposer d'un fondement de diffusion, d'une autorisation adaptée ou d'un droit d'exploitation documenté.",
                                "L'association se réserve le droit de retirer immédiatement tout média en cas de doute sur les droits, l'autorisation ou la conformité juridique.",
                            ],
                        ],
                        [
                            'title' => 'Responsabilité',
                            'items' => [
                                "Malgré le soin apporté à la publication, l'association et l'auteur du site ne garantissent pas l'absence totale d'erreur, d'omission ou d'interruption.",
                                "Les contenus externes accessibles par lien restent sous la responsabilité de leurs propres éditeurs.",
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'privacy-policy',
                    'title' => 'Politique de confidentialité',
                    'summary' => "Données traitées, comptes membres, cookies, conservation et droits des personnes.",
                    'sections' => [
                        [
                            'title' => 'Données potentiellement traitées',
                            'items' => [
                                "Nom, prénom, date de naissance facultative, email, mot de passe hashé et description de profil saisis lors de l'inscription.",
                                "Métadonnées minimales nécessaires à la sécurité du service, au consentement, à la session membre, à la modération éditoriale et aux journaux serveurs.",
                                "Le cas échéant, informations liées aux médias, à leurs ayants droit, à leur durée de diffusion et aux autorisations de publication.",
                            ],
                        ],
                        [
                            'title' => 'Finalités du traitement',
                            'items' => [
                                "Créer et maintenir un compte membre, permettre la connexion par email, l'édition du profil et la création d'articles en attente de modération.",
                                "Administrer le site, assurer la sécurité technique, la modération et conserver la preuve du consentement lorsque cela est nécessaire.",
                                "Préparer la gestion future d'une médiathèque, d'un espace merch et d'une base Oracle structurée pour la maintenance long terme.",
                            ],
                        ],
                        [
                            'title' => 'Cookies, stockage et conservation',
                            'items' => [
                                "Un consentement obligatoire de première entrée est présenté avant l'accès au contenu du site.",
                                "Le site utilise des cookies ou mécanismes similaires strictement limités à la session, au consentement et aux préférences d'affichage.",
                                "Les durées de conservation définitives devront être validées et documentées par l'association avant la mise en production finale.",
                            ],
                        ],
                        [
                            'title' => 'Droits des personnes',
                            'items' => [
                                "Chaque personne dispose des droits d'accès, de rectification, d'effacement, de limitation, d'opposition et, selon les cas, de portabilité.",
                                "Les demandes devront être traitées par le contact officiel de l'association lorsqu'il sera publié.",
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'terms-of-use',
                    'title' => "Conditions d'utilisation",
                    'summary' => "Usage du site, espace membre, articles, médias, merch et cadre associatif lié au jeu d'échecs.",
                    'sections' => [
                        [
                            'title' => 'Usage général',
                            'items' => [
                                "L'utilisateur s'engage à consulter le site dans le respect de la loi, de l'ordre public et de l'objet associatif.",
                                "Toute tentative de détournement, extraction massive, nuisance technique, usurpation de compte ou publication malveillante est interdite.",
                            ],
                        ],
                        [
                            'title' => 'Comptes membres',
                            'items' => [
                                "Chaque compte doit être créé avec un email valable et un mot de passe confidentiel choisi par l'utilisateur.",
                                "L'utilisateur reste responsable des informations qu'il saisit dans son profil et dans les contenus qu'il soumet.",
                            ],
                        ],
                        [
                            'title' => 'Articles, médias et merch',
                            'items' => [
                                "Les articles, médias du club et contenus merch peuvent être affichés, modifiés, refusés ou retirés selon les besoins de modération, de conformité ou de publication.",
                                "Aucune offre commerciale, publication ou diffusion de média ne vaut engagement définitif tant qu'elle n'a pas été publiée officiellement par l'association.",
                            ],
                        ],
                    ],
                ],
            ],
            'consent' => [
                'cookie_name' => 'site_consent',
                'title' => "Validation obligatoire avant l'entrée",
                'intro' => "Avant d'accéder au site, vous devez accepter la politique de confidentialité, les conditions d'utilisation et l'usage des cookies essentiels et de préférence.",
                'checks' => [
                    "J'ai lu la politique de confidentialité, y compris les informations sur les comptes membres, les images, les vidéos et la connexion par email.",
                    "J'accepte les cookies essentiels de session, de consentement et de préférence de thème.",
                    "J'ai pris connaissance des mentions légales, de la modération des articles, des médias et de la propriété intellectuelle.",
                ],
                'button' => "Accepter et entrer sur le site",
            ],
            'auth_modal' => [
                'title' => 'Connexion et inscription',
                'login_title' => 'Se connecter',
                'register_title' => 'Créer un compte',
            ],
        ];
    }

    public function getPages(): array
    {
        return [
            'accueil' => [
                'title' => 'Accueil',
                'view' => 'home.php',
                'meta_description' => "Accueil, guide des pièces, connexion membre, cookies et cadre légal.",
                'hero_title' => "Le jeu d'échecs, pièce par pièce, membre par membre.",
                'hero_text' => "Ce site combine un espace membre local, un cadre de publication responsable, une gestion des cookies et des contenus accessibles après consentement.",
                'hero_note' => "Connexion par email, thème clair ou sombre, menu burger et articles en attente de modération.",
            ],
            'guide' => [
                'title' => 'Guide',
                'view' => 'guide.php',
                'meta_description' => "Guides de stratégie, principes de jeu et cartes de progression.",
                'intro' => "Des cartes simples pour rappeler les principes stratégiques les plus utiles à un joueur de club.",
            ],
            'mediatheque' => [
                'title' => 'Médiathèque',
                'view' => 'media-library.php',
                'meta_description' => "Galeries photos, vidéos et espaces médiathèque du club.",
                'intro' => "Cette page regroupera les photos et vidéos du club dès qu'elles seront publiées officiellement.",
            ],
            'articles' => [
                'title' => 'Articles',
                'view' => 'articles.php',
                'meta_description' => "Articles publics, soumissions membres et modération future.",
                'intro' => "Les membres peuvent proposer des articles, mais leur publication publique reste soumise à validation.",
            ],
            'merch' => [
                'title' => 'Merch',
                'view' => 'merch.php',
                'meta_description' => "Espace merch, catalogue et ventes à venir.",
                'intro' => "Cette page accueillera le catalogue de produits du club lorsqu'une publication officielle sera prête.",
            ],
            'club' => [
                'title' => 'Le club',
                'view' => 'club.php',
                'meta_description' => "Présentation institutionnelle et cadre de publication du club.",
                'intro' => "Cette page est réservée à la présentation officielle du club, de ses responsables et de son fonctionnement validé.",
            ],
            'activites' => [
                'title' => 'Activités',
                'view' => 'activities.php',
                'meta_description' => "Organisation des activités, documents utiles et publication officielle.",
                'intro' => "Cette page accueillera les activités, les documents et le calendrier uniquement lorsqu'ils auront été officiellement validés.",
            ],
            'contact' => [
                'title' => 'Contact',
                'view' => 'contact.php',
                'meta_description' => "Contact, crédits du site, publication associative et cadre de confidentialité.",
                'intro' => "Cette page centralise les responsables nommés, le cadre de publication et les informations de contact lorsqu'elles sont officiellement disponibles.",
            ],
            'profil' => [
                'title' => 'Profil',
                'view' => 'profile.php',
                'meta_description' => "Profil membre, description éditable et informations personnelles.",
                'intro' => "Cette page permet au membre connecté de modifier son profil, sa description et ses informations personnelles.",
            ],
            'parametres' => [
                'title' => 'Paramètres',
                'view' => 'settings.php',
                'meta_description' => "Paramètres, thème, cookies et informations de session.",
                'intro' => "Cette page regroupe les préférences du site, les informations sur les cookies et les réglages d'interface.",
            ],
        ];
    }

    public function getGuideCards(): array
    {
        return [
            [
                'tag' => 'Ouverture',
                'title' => 'Contrôler le centre',
                'text' => "Occuper ou attaquer les cases centrales donne plus d'espace et facilite le développement.",
            ],
            [
                'tag' => 'Développement',
                'title' => 'Sortir les pièces vite',
                'text' => "Activer rapidement cavaliers et fous évite de perdre du temps et prépare le roque.",
            ],
            [
                'tag' => 'Sécurité',
                'title' => "Mettre le roi à l'abri",
                'text' => "Roquer tôt permet souvent de connecter les tours et de réduire les menaces centrales.",
            ],
            [
                'tag' => 'Tactique',
                'title' => 'Chercher les fourchettes',
                'text' => "Les fourchettes, en particulier avec le cavalier, peuvent faire gagner du matériel rapidement.",
            ],
            [
                'tag' => 'Milieu de jeu',
                'title' => 'Améliorer la pièce la moins active',
                'text' => "Quand aucun coup tactique n'apparaît, améliorer sa pièce la moins bien placée reste un excellent réflexe.",
            ],
            [
                'tag' => 'Finale',
                'title' => 'Activer le roi',
                'text' => "En finale, le roi devient une pièce forte et doit souvent participer activement.",
            ],
        ];
    }

    public function getMediaCards(): array
    {
        return [
            [
                'type' => 'Galerie photo',
                'title' => 'Albums du club',
                'text' => "Aucun album public n'est encore publié. Le cadre est prêt pour accueillir les prochains reportages.",
                'status' => 'En attente',
            ],
            [
                'type' => 'Vidéo',
                'title' => 'Captations et temps forts',
                'text' => "Cette zone accueillera les vidéos du club, interviews, animations et résumés d'événements.",
                'status' => 'En attente',
            ],
            [
                'type' => 'Archives',
                'title' => 'Mémoires visuelles',
                'text' => "Les archives photo ou vidéo resteront publiées seulement après vérification des droits et autorisations.",
                'status' => 'Contrôle des droits',
            ],
        ];
    }

    public function getMerchCards(): array
    {
        return [
            [
                'type' => 'Textile',
                'title' => 'Collection club',
                'text' => "Espace prévu pour les textiles officiels, à publier uniquement lorsqu'une offre aura été validée.",
                'status' => 'Bientôt disponible',
            ],
            [
                'type' => 'Accessoires',
                'title' => 'Objets utilitaires',
                'text' => "Le site pourra présenter accessoires, petits objets et matériel dès que le catalogue sera confirmé.",
                'status' => 'Bientôt disponible',
            ],
            [
                'type' => 'Matériel',
                'title' => 'Sélection de produits',
                'text' => "Les références, stocks, tarifs et conditions de vente devront être officiellement publiés avant toute diffusion.",
                'status' => 'Publication encadrée',
            ],
        ];
    }

    public function getNotFoundData(): array
    {
        return [
            'title' => 'Page introuvable',
            'meta_description' => "La page demandée n'existe pas ou n'est plus disponible.",
            'message' => "La page demandée n'existe pas. Le plus simple est de revenir à l'accueil puis de reprendre la navigation.",
        ];
    }
}
