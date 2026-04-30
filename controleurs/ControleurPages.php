<?php

declare(strict_types=1);

final class ControleurPages
{
    public function __construct(
        private ModeleSite $modeleSite,
        private DepotUtilisateurs $depotUtilisateurs,
        private DepotArticles $depotArticles,
        private ServiceChessCom $serviceChessCom,
        private array $messagesFlash,
        private array $etatFormulaire
    ) {
    }

    public function afficher(string $segment): string
    {
        $donneesSite = $this->modeleSite->obtenirDonneesSite();
        $pages = $this->modeleSite->obtenirPages();
        $utilisateurCourant = $this->depotUtilisateurs->trouverParIdentifiant(isset($_SESSION['identifiant_utilisateur']) ? (string) $_SESSION['identifiant_utilisateur'] : null);

        $donneesSite['theme'] = theme_courant();
        $donneesSite['jeton_csrf'] = jeton_csrf();
        $donneesSite['messages_flash'] = $this->messagesFlash;
        $donneesSite['etat_formulaire'] = $this->etatFormulaire;
        $donneesSite['page_courante'] = $segment;
        $donneesSite['authentification'] = $this->construireDonneesAuthentification($utilisateurCourant);
        $donneesSite['cartes_guide'] = $this->modeleSite->obtenirCartesGuide();
        $donneesSite['cartes_mediatheque'] = $this->modeleSite->obtenirCartesMediatheque();
        $donneesSite['cartes_boutique'] = $this->modeleSite->obtenirCartesBoutique();
        $donneesSite['articles_publies'] = $this->depotArticles->trouverPublies();
        $donneesSite['mes_articles'] = $utilisateurCourant !== null ? $this->depotArticles->trouverParIdentifiantAuteur((string) $utilisateurCourant['identifiant']) : [];
        $donneesSite['chess_com'] = [
            'statut' => 'absent',
            'pseudo' => '',
            'message' => '',
            'classements' => [],
            'joueur' => null,
            'note_statistiques' => '',
            'date_recuperation_libelle' => '',
            'source_cache' => '',
            'status' => 'missing',
            'profile_url' => '',
            'player' => null,
            'ratings' => [],
            'stats_note' => '',
            'fetched_at_label' => '',
        ];

        if ($segment === 'profil' && $utilisateurCourant !== null) {
            $donneesSite['chess_com'] = $this->serviceChessCom->recupererInstantaneJoueur(
                (string) ($utilisateurCourant['pseudo_chess'] ?? '')
            );
        }

        $donneesPage = $pages[$segment] ?? null;

        if ($donneesPage === null && $segment === 'boutique' && isset($pages['merch'])) {
            $donneesPage = $pages['merch'];
        }

        if ($donneesPage === null) {
            http_response_code(404);
            $pageCourante = 'introuvable';
            $donneesPage = $this->modeleSite->obtenirDonneesIntrouvable();
            $titrePage = 'Page introuvable';
            $fichierVue = __DIR__ . '/../vues/pages/introuvable.php';
        } else {
            $donneesPage['titre'] = (string) ($donneesPage['titre'] ?? $donneesPage['title'] ?? '');
            $donneesPage['vue'] = (string) ($donneesPage['vue'] ?? $donneesPage['view'] ?? '');
            $donneesPage['description_meta'] = (string) ($donneesPage['description_meta'] ?? $donneesPage['meta_description'] ?? '');
            $donneesPage['titre_hero'] = (string) ($donneesPage['titre_hero'] ?? $donneesPage['hero_title'] ?? '');
            $donneesPage['texte_hero'] = (string) ($donneesPage['texte_hero'] ?? $donneesPage['hero_text'] ?? '');
            $donneesPage['note_hero'] = (string) ($donneesPage['note_hero'] ?? $donneesPage['hero_note'] ?? '');
            $pageCourante = $segment;
            $titrePage = $donneesPage['titre'] !== '' ? $donneesPage['titre'] : 'Page';
            $fichierVue = __DIR__ . '/../vues/pages/' . $donneesPage['vue'];
        }

        $metaTitre = $titrePage . ' | ' . $donneesSite['brand'];
        $descriptionMeta = (string) ($donneesPage['description_meta'] ?? $donneesPage['meta_description'] ?? $donneesSite['accroche'] ?? $donneesSite['tagline']);

        ob_start();
        require __DIR__ . '/../vues/mise-en-page.php';

        return (string) ob_get_clean();
    }

    private function construireDonneesAuthentification(?array $utilisateur): array
    {
        if ($utilisateur === null) {
            return [
                'est_connecte' => false,
                'nom_affichage' => '',
                'utilisateur' => null,
                'is_authenticated' => false,
                'display_name' => '',
                'user' => null,
            ];
        }

        $nomAffichage = trim((string) ($utilisateur['prenom'] ?? '') . ' ' . (string) ($utilisateur['nom'] ?? ''));

        return [
            'est_connecte' => true,
            'nom_affichage' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? ''),
            'utilisateur' => $utilisateur,
            'is_authenticated' => true,
            'display_name' => $nomAffichage !== '' ? $nomAffichage : (string) ($utilisateur['courriel'] ?? ''),
            'user' => [
                ...$utilisateur,
                'email' => $utilisateur['courriel'] ?? '',
                'first_name' => $utilisateur['prenom'] ?? '',
                'last_name' => $utilisateur['nom'] ?? '',
                'birth_date' => $utilisateur['date_naissance'] ?? '',
                'profile_description' => $utilisateur['description_profil'] ?? '',
                'chess_username' => $utilisateur['pseudo_chess'] ?? '',
            ],
        ];
    }
}
