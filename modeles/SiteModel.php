<?php

declare(strict_types=1);

final class SiteModel
{
    public function getSiteData(): array
    {
        return [
            'brand' => 'Association d Echecs',
            'city' => 'Informations officielles en cours de validation',
            'tagline' => 'Cadre legal, espace membre, publication responsable et contenus valides par l association.',
            'cta' => 'Connexion / inscription',
            'address' => 'Coordonnees postales a publier',
            'email' => 'Adresse de contact a publier',
            'phone' => 'Numero de contact a publier',
            'primary_nav' => [
                ['slug' => 'accueil', 'label' => 'Accueil'],
                ['slug' => 'guide', 'label' => 'Guide'],
                ['slug' => 'mediatheque', 'label' => 'Mediatheque'],
                ['slug' => 'articles', 'label' => 'Articles'],
                ['slug' => 'merch', 'label' => 'Merch'],
            ],
            'secondary_nav' => [
                ['slug' => 'club', 'label' => 'Le club'],
                ['slug' => 'activites', 'label' => 'Activites'],
                ['slug' => 'contact', 'label' => 'Contact'],
            ],
            'stats' => [
                [
                    'value' => '01',
                    'label' => 'Espace membre',
                    'text' => 'Connexion locale par email, profil personnel et modifications du profil depuis un espace dedie.',
                ],
                [
                    'value' => '02',
                    'label' => 'Cookies encadres',
                    'text' => 'Consentement obligatoire, cookie de preference visuelle et cookie de session pour les utilisateurs connectes.',
                ],
                [
                    'value' => '03',
                    'label' => 'Publication moderee',
                    'text' => 'Les articles proposes par les membres restent en attente de validation par un futur role administrateur.',
                ],
            ],
            'values' => [
                [
                    'title' => 'Publication officielle',
                    'text' => 'Les zones publiques restent disponibles, mais les informations sensibles ou institutionnelles sont publiees seulement apres validation.',
                ],
                [
                    'title' => 'Experience membre',
                    'text' => 'Le site accueille des comptes membres, un profil editable, un espace de redaction et des reglages persistants.',
                ],
                [
                    'title' => 'Conformite continue',
                    'text' => 'Mentions legales, politique de confidentialite, cookies, propriete intellectuelle et moderation sont explicites.',
                ],
            ],
            'schedule' => [
                [
                    'day' => 'Bloc 01',
                    'slot' => 'A publier',
                    'title' => 'Identite associative',
                    'text' => 'Le nom officiel, le siege, les responsables et les informations publiques seront affiches ici.',
                ],
                [
                    'day' => 'Bloc 02',
                    'slot' => 'A publier',
                    'title' => 'Organisation pratique',
                    'text' => 'Les horaires, le lieu de jeu, les groupes et les modalites d accueil seront ajoutes ici.',
                ],
                [
                    'day' => 'Bloc 03',
                    'slot' => 'A publier',
                    'title' => 'Coordonnees et documents',
                    'text' => 'Les contacts, les pieces utiles et les informations de communication seront centralises ici.',
                ],
            ],
            'activities' => [
                [
                    'tag' => 'Section',
                    'title' => 'Public concerne',
                    'text' => 'Cette carte recevra les publics et categories officiellement communiques par l association.',
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Formats de pratique',
                    'text' => 'Cours, jeu libre, animation ou accompagnement seront renseignes apres validation associative.',
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Calendrier',
                    'text' => 'Le planning des activites sera ajoute ici lorsqu il sera officiellement etabli.',
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Documents utiles',
                    'text' => 'Bulletin d adhesion, reglement interieur et documents de reference pourront etre centralises ici.',
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Acces et inscription',
                    'text' => 'Les conditions d inscription, d adhesion et de participation seront affichees de facon claire.',
                ],
                [
                    'tag' => 'Section',
                    'title' => 'Communication officielle',
                    'text' => 'Les annonces, actualites et mises a jour valides par l association seront publiees dans ce cadre.',
                ],
            ],
            'join_steps' => [
                'Creer un compte avec son nom, son prenom, son email et un mot de passe securise.',
                'Mettre a jour son profil, sa description personnelle et ses preferences une fois connecte.',
                'Utiliser les espaces guide, articles, mediatheque ou merch selon les contenus publiquement disponibles.',
            ],
            'compliance_points' => [
                'Le consentement cookies doit etre accepte avant l entree sur le site.',
                'Les comptes membres reposent sur une session PHP, un mot de passe hashé et des formulaires proteges.',
                'Les articles proposes sont crees par les membres mais restent en attente de validation par un futur administrateur.',
                'Le design, les contenus, les medias et les publications du site relevent de la propriete intellectuelle.',
            ],
            'piece_carousel' => [
                [
                    'name' => 'Pion',
                    'glyph' => '♙',
                    'role' => 'Le pion avance case par case, capture en diagonale et peut se promouvoir en fin de course.',
                ],
                [
                    'name' => 'Tour',
                    'glyph' => '♖',
                    'role' => 'La tour se deplace en ligne droite sur les rangs et les colonnes et domine les couloirs ouverts.',
                ],
                [
                    'name' => 'Cavalier',
                    'glyph' => '♘',
                    'role' => 'Le cavalier se deplace en L et peut sauter par-dessus les autres pieces.',
                ],
                [
                    'name' => 'Fou',
                    'glyph' => '♗',
                    'role' => 'Le fou se deplace en diagonale et controle de longues trajectoires sur une seule couleur.',
                ],
                [
                    'name' => 'Reine',
                    'glyph' => '♕',
                    'role' => 'La reine combine les mouvements de la tour et du fou et reste la piece la plus mobile.',
                ],
                [
                    'name' => 'Roi',
                    'glyph' => '♔',
                    'role' => 'Le roi avance d une case autour de lui et sa securite decide de toute la partie.',
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
                    'purpose' => 'Maintenir la session des membres connectes et securiser l acces au profil.',
                ],
                [
                    'name' => 'site_consent',
                    'type' => 'Essentiel',
                    'purpose' => 'Conserver la preuve du consentement obligatoire a la confidentialite et aux cookies.',
                ],
                [
                    'name' => 'site_theme',
                    'type' => 'Preference',
                    'purpose' => 'Retenir le theme clair ou sombre choisi dans l interface.',
                ],
            ],
            'legal_documents' => [
                [
                    'id' => 'legal-notice',
                    'title' => 'Mentions legales',
                    'summary' => 'Edition, publication, donnees, moderation, propriete intellectuelle et responsabilites.',
                    'sections' => [
                        [
                            'title' => 'Edition et publication',
                            'items' => [
                                'Conception et developpement du site : Matthéo Mullois.',
                                'Publication associative et validation institutionnelle : Jean Patrick JORON.',
                                'Les informations publiques de l association doivent etre completees et validees par les responsables habilites avant publication definitive.',
                            ],
                        ],
                        [
                            'title' => 'Espace membre et publications',
                            'items' => [
                                'Les utilisateurs peuvent creer un compte membre via leur email et un mot de passe securise.',
                                'Les articles soumis depuis l espace membre restent en attente de validation par un futur role administrateur avant publication publique.',
                            ],
                        ],
                        [
                            'title' => 'Propriete intellectuelle',
                            'items' => [
                                'La structure, le design, les textes, les medias, les scripts, les visuels, les logos et les documents sont proteges par le droit d auteur et, le cas echeant, par le droit des marques.',
                                'Toute reproduction, adaptation, republication ou diffusion sans autorisation ecrite prealable est interdite, hors exceptions legales.',
                            ],
                        ],
                        [
                            'title' => 'Responsabilite',
                            'items' => [
                                'Malgre le soin apporte a la publication, l association et l auteur du site ne garantissent pas l absence totale d erreur, d omission ou d interruption.',
                                'Les contenus externes accessibles par lien restent sous la responsabilite de leurs propres editeurs.',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'privacy-policy',
                    'title' => 'Politique de confidentialite',
                    'summary' => 'Donnees traitees, comptes membres, cookies, conservation et droits des personnes.',
                    'sections' => [
                        [
                            'title' => 'Donnees potentiellement traitees',
                            'items' => [
                                'Nom, prenom, date de naissance facultative, email, mot de passe hashé et description de profil saisis lors de l inscription.',
                                'Donnees techniques minimales necessaires a la securite du service, au consentement, a la session membre et aux journaux serveurs.',
                            ],
                        ],
                        [
                            'title' => 'Finalites du traitement',
                            'items' => [
                                'Creer et maintenir un compte membre, permettre la connexion par email, l edition du profil et la creation d articles en attente de moderation.',
                                'Administrer le site, assurer la securite technique, la moderation et conserver la preuve du consentement lorsque cela est necessaire.',
                            ],
                        ],
                        [
                            'title' => 'Cookies et stockage',
                            'items' => [
                                'Un consentement obligatoire de premiere entree est presente avant l acces au contenu du site.',
                                'Le site utilise des cookies ou mecanismes similaires strictement limites a la session, au consentement et aux preferences d affichage.',
                            ],
                        ],
                        [
                            'title' => 'Droits des personnes',
                            'items' => [
                                'Chaque personne dispose des droits d acces, de rectification, d effacement, de limitation, d opposition et, selon les cas, de portabilite.',
                                'Les demandes devront etre traitees par le contact officiel de l association lorsqu il sera publie.',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'terms-of-use',
                    'title' => 'Conditions d utilisation',
                    'summary' => 'Usage du site, espace membre, articles, medias, merch et cadre associatif lie au jeu d echecs.',
                    'sections' => [
                        [
                            'title' => 'Usage general',
                            'items' => [
                                'L utilisateur s engage a consulter le site dans le respect de la loi, de l ordre public et de l objet associatif.',
                                'Toute tentative de detournement, extraction massive, nuisance technique, usurpation de compte ou publication malveillante est interdite.',
                            ],
                        ],
                        [
                            'title' => 'Comptes membres',
                            'items' => [
                                'Chaque compte doit etre cree avec un email valable et un mot de passe confidentiel choisi par l utilisateur.',
                                'L utilisateur reste responsable des informations qu il saisit dans son profil et dans les contenus qu il soumet.',
                            ],
                        ],
                        [
                            'title' => 'Contenus, medias et merch',
                            'items' => [
                                'Les articles de presse, guides, medias du club et espaces merch peuvent etre affiches, modifies ou retires selon les besoins de moderation ou de publication.',
                                'Aucune offre commerciale, publication ou diffusion de media ne vaut engagement definitif tant qu elle n a pas ete publiee officiellement par l association.',
                            ],
                        ],
                    ],
                ],
            ],
            'consent' => [
                'cookie_name' => 'site_consent',
                'title' => 'Validation obligatoire avant l entree',
                'intro' => 'Avant d acceder au site, vous devez accepter la politique de confidentialite, les conditions d utilisation et l usage des cookies essentiels et de preference.',
                'checks' => [
                    'J ai lu la politique de confidentialite, y compris les informations sur les comptes membres et la connexion par email.',
                    'J accepte les cookies essentiels de session, de consentement et de preference de theme.',
                    'J ai pris connaissance des mentions legales, de la moderation des articles et de la propriete intellectuelle.',
                ],
                'button' => 'Accepter et entrer sur le site',
            ],
            'auth_modal' => [
                'title' => 'Connexion et inscription',
                'login_title' => 'Se connecter',
                'register_title' => 'Creer un compte',
            ],
        ];
    }

    public function getPages(): array
    {
        return [
            'accueil' => [
                'title' => 'Accueil',
                'view' => 'home.php',
                'meta_description' => 'Accueil, guide des pieces, connexion membre, cookies et cadre legal.',
                'hero_title' => 'Le jeu d echecs, piece par piece, membre par membre.',
                'hero_text' => 'Ce site combine un espace membre local, un cadre de publication responsable, une gestion des cookies et des contenus accessibles apres consentement.',
                'hero_note' => 'Connexion par email, theme clair ou sombre, burger menu et articles en attente de moderation.',
            ],
            'guide' => [
                'title' => 'Guide',
                'view' => 'guide.php',
                'meta_description' => 'Guides de strategie, principes de jeu et cartes de progression.',
                'intro' => 'Des cartes simples pour rappeler les principes strategiques les plus utiles a un joueur de club.',
            ],
            'mediatheque' => [
                'title' => 'Mediatheque',
                'view' => 'media-library.php',
                'meta_description' => 'Galeries photos, videos et espaces mediatheque du club.',
                'intro' => 'Cette page regroupera les photos et videos du club des qu elles seront publiees officiellement.',
            ],
            'articles' => [
                'title' => 'Articles',
                'view' => 'articles.php',
                'meta_description' => 'Articles publics, soumissions membres et moderation future.',
                'intro' => 'Les membres peuvent proposer des articles, mais leur publication publique reste soumise a validation.',
            ],
            'merch' => [
                'title' => 'Merch',
                'view' => 'merch.php',
                'meta_description' => 'Espace merch, catalogue et ventes a venir.',
                'intro' => 'Cette page accueillera le catalogue de produits du club lorsqu une publication officielle sera prete.',
            ],
            'club' => [
                'title' => 'Le club',
                'view' => 'club.php',
                'meta_description' => 'Presentation institutionnelle et cadre de publication du club.',
                'intro' => 'Cette page est reservee a la presentation officielle du club, de ses responsables et de son fonctionnement valide.',
            ],
            'activites' => [
                'title' => 'Activites',
                'view' => 'activities.php',
                'meta_description' => 'Organisation des activites, documents utiles et publication officielle.',
                'intro' => 'Cette page accueillera les activites, les documents et le calendrier uniquement lorsqu ils auront ete officiellement valides.',
            ],
            'contact' => [
                'title' => 'Contact',
                'view' => 'contact.php',
                'meta_description' => 'Contact, credits du site, publication associative et cadre de confidentialite.',
                'intro' => 'Cette page centralise les responsables nommes, le cadre de publication et les informations de contact lorsqu elles sont officiellement disponibles.',
            ],
            'profil' => [
                'title' => 'Profil',
                'view' => 'profile.php',
                'meta_description' => 'Profil membre, description editable et informations personnelles.',
                'intro' => 'Cette page permet au membre connecte de modifier son profil, sa description et ses informations personnelles.',
            ],
            'parametres' => [
                'title' => 'Parametres',
                'view' => 'settings.php',
                'meta_description' => 'Parametres, theme, cookies et informations de session.',
                'intro' => 'Cette page regroupe les preferences du site, les informations sur les cookies et les reglages d interface.',
            ],
        ];
    }

    public function getGuideCards(): array
    {
        return [
            [
                'tag' => 'Ouverture',
                'title' => 'Controler le centre',
                'text' => 'Occuper ou attaquer les cases centrales donne plus d espace et facilite le developpement.',
            ],
            [
                'tag' => 'Developpement',
                'title' => 'Sortir les pieces vite',
                'text' => 'Activer rapidement cavaliers et fous evite de perdre du temps et prepare le roque.',
            ],
            [
                'tag' => 'Securite',
                'title' => 'Mettre le roi a l abri',
                'text' => 'Roquer tot permet souvent de connecter les tours et de reduire les menaces centrales.',
            ],
            [
                'tag' => 'Tactique',
                'title' => 'Chercher les fourchettes',
                'text' => 'Les fourchettes, en particulier avec le cavalier, peuvent faire gagner du materiel rapidement.',
            ],
            [
                'tag' => 'Milieu de jeu',
                'title' => 'Ameliorer la piece la moins active',
                'text' => 'Quand aucun coup tactique n apparait, ameliorer sa piece la moins bien placee reste un excellent reflexe.',
            ],
            [
                'tag' => 'Finale',
                'title' => 'Activer le roi',
                'text' => 'En finale, le roi devient une piece forte et doit souvent participer activement.',
            ],
        ];
    }

    public function getMediaCards(): array
    {
        return [
            [
                'type' => 'Galerie photo',
                'title' => 'Albums du club',
                'text' => 'Aucun album public n est encore publie. Le cadre est pret pour accueillir les prochains reportages.',
                'status' => 'En attente',
            ],
            [
                'type' => 'Video',
                'title' => 'Captations et temps forts',
                'text' => 'Cette zone accueillera les videos du club, interviews, animations et resumes d evenements.',
                'status' => 'En attente',
            ],
            [
                'type' => 'Archives',
                'title' => 'Memoires visuelles',
                'text' => 'Les archives photo ou video resteront publiees seulement apres verification des droits et autorisations.',
                'status' => 'Controle des droits',
            ],
        ];
    }

    public function getMerchCards(): array
    {
        return [
            [
                'type' => 'Textile',
                'title' => 'Collection club',
                'text' => 'Espace prevu pour les textiles officiels, a publier uniquement lorsqu une offre aura ete validee.',
                'status' => 'Bientot disponible',
            ],
            [
                'type' => 'Accessoires',
                'title' => 'Objets utilitaires',
                'text' => 'Le site pourra presenter accessoires, petits objets et materiel des que le catalogue sera confirme.',
                'status' => 'Bientot disponible',
            ],
            [
                'type' => 'Materiel',
                'title' => 'Selection de produits',
                'text' => 'Les references, stocks, tarifs et conditions de vente devront etre officiellement publies avant toute diffusion.',
                'status' => 'Publication encadree',
            ],
        ];
    }

    public function getNotFoundData(): array
    {
        return [
            'title' => 'Page introuvable',
            'meta_description' => 'La page demandee n existe pas ou n est plus disponible.',
            'message' => 'La page demandee n existe pas. Le plus simple est de revenir a l accueil puis de reprendre la navigation.',
        ];
    }
}
