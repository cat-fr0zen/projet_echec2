<?php

declare(strict_types=1);

/**
 * ServiceGoogleAvis
 *
 * Intégration lecture seule avec Google Places API (New) pour récupérer
 * la note globale et quelques avis publics d'un lieu.
 *
 * Sécurité:
 * - clé API lue uniquement depuis l'environnement
 * - aucun secret en dur dans le code
 * - cache local pour limiter les appels externes
 */
final class ServiceGoogleAvis
{
    private const URL_SEARCH_TEXT = 'https://places.googleapis.com/v1/places:searchText';
    private const URL_PLACE_DETAILS = 'https://places.googleapis.com/v1/places/%s';
    private const DUREE_CACHE_SUCCES = 43200;
    private const DUREE_CACHE_ERREUR = 1800;

    public function __construct(
        private string $dossierCache,
        private string $cleApi = '',
        private string $agentUtilisateur = 'association-echecs-site/1.0'
    ) {
        $this->cleApi = trim($this->cleApi);

        if (!is_dir($this->dossierCache)) {
            mkdir($this->dossierCache, 0777, true);
        }
    }

    public function recupererAvisLieu(string $identifiantCache, string $requeteRecherche): array
    {
        $identifiantCacheNormalise = $this->normaliserIdentifiantCache($identifiantCache);
        $requeteRecherche = trim($requeteRecherche);

        if ($requeteRecherche === '') {
            return $this->ajouterAliasCompatibilite(
                $this->construireInstantaneIndisponible('requete_absente', 'Aucune requête Google n’a été définie.')
            );
        }

        if ($this->cleApi === '') {
            return $this->ajouterAliasCompatibilite(
                $this->construireInstantaneIndisponible('non_configure', 'Google Places API n’est pas configurée.')
            );
        }

        $instantaneCache = $this->lireCache($identifiantCacheNormalise);

        if ($instantaneCache !== null) {
            return $this->ajouterAliasCompatibilite($instantaneCache);
        }

        $reponseRecherche = $this->effectuerRequeteJson(
            self::URL_SEARCH_TEXT,
            'POST',
            [
                'textQuery' => $requeteRecherche,
                'languageCode' => 'fr',
                'regionCode' => 'FR',
                'pageSize' => 1,
            ],
            [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $this->cleApi,
                'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress,places.googleMapsUri',
            ]
        );

        $premierLieu = $reponseRecherche['donnees']['places'][0] ?? null;

        if (($reponseRecherche['code_statut'] ?? 0) !== 200 || !is_array($premierLieu)) {
            $instantane = $this->construireInstantaneIndisponible(
                'recherche_impossible',
                'Impossible de récupérer les avis Google pour le moment.'
            );
            $this->ecrireCache($identifiantCacheNormalise, $instantane, self::DUREE_CACHE_ERREUR);

            return $this->ajouterAliasCompatibilite($instantane);
        }

        $identifiantLieu = (string) ($premierLieu['id'] ?? '');

        if ($identifiantLieu === '') {
            $instantane = $this->construireInstantaneIndisponible(
                'lieu_introuvable',
                'La fiche Google du club n’a pas été trouvée.'
            );
            $this->ecrireCache($identifiantCacheNormalise, $instantane, self::DUREE_CACHE_ERREUR);

            return $this->ajouterAliasCompatibilite($instantane);
        }

        $reponseDetails = $this->effectuerRequeteJson(
            sprintf(self::URL_PLACE_DETAILS, rawurlencode($identifiantLieu)),
            'GET',
            null,
            [
                'Content-Type: application/json',
                'X-Goog-Api-Key: ' . $this->cleApi,
                'X-Goog-FieldMask: id,displayName,formattedAddress,googleMapsUri,rating,userRatingCount,reviews',
            ]
        );

        if (($reponseDetails['code_statut'] ?? 0) !== 200 || !is_array($reponseDetails['donnees'] ?? null)) {
            $instantane = $this->construireInstantaneIndisponible(
                'details_impossibles',
                'Les détails Google du club sont temporairement indisponibles.'
            );
            $this->ecrireCache($identifiantCacheNormalise, $instantane, self::DUREE_CACHE_ERREUR);

            return $this->ajouterAliasCompatibilite($instantane);
        }

        $instantane = $this->construireInstantaneSucces($premierLieu, $reponseDetails['donnees']);
        $this->ecrireCache($identifiantCacheNormalise, $instantane, self::DUREE_CACHE_SUCCES);

        return $this->ajouterAliasCompatibilite($instantane);
    }

    private function construireInstantaneSucces(array $lieuRecherche, array $detailsLieu): array
    {
        $noteMoyenne = $this->versFlottantNullable($detailsLieu['rating'] ?? null);
        $nombreAvis = $this->versEntierNullable($detailsLieu['userRatingCount'] ?? null);
        $dateRecuperation = gmdate('c');
        $avisNormalises = [];

        foreach (($detailsLieu['reviews'] ?? []) as $avis) {
            if (!is_array($avis)) {
                continue;
            }

            $texteAvis = $this->extraireTexteAvis($avis);

            if ($texteAvis === '') {
                continue;
            }

            $noteAvis = $this->versFlottantNullable($avis['rating'] ?? null);
            $attributionAuteur = is_array($avis['authorAttribution'] ?? null) ? $avis['authorAttribution'] : [];

            $avisNormalises[] = [
                'auteur' => (string) ($attributionAuteur['displayName'] ?? 'Avis Google'),
                'photo_auteur' => (string) ($attributionAuteur['photoUri'] ?? ''),
                'profil_auteur' => (string) ($attributionAuteur['uri'] ?? ''),
                'note' => $noteAvis,
                'note_libelle' => $this->formatterNote($noteAvis, true),
                'date_relative' => (string) ($avis['relativePublishTimeDescription'] ?? ''),
                'texte' => $texteAvis,
                'lien_google_maps' => (string) ($avis['googleMapsUri'] ?? ($detailsLieu['googleMapsUri'] ?? '')),
            ];
        }

        return [
            'statut' => 'disponible',
            'nom_lieu' => (string) ($detailsLieu['displayName']['text'] ?? $lieuRecherche['displayName']['text'] ?? ''),
            'adresse' => (string) ($detailsLieu['formattedAddress'] ?? $lieuRecherche['formattedAddress'] ?? ''),
            'note_moyenne' => $noteMoyenne,
            'note_moyenne_libelle' => $this->formatterNote($noteMoyenne, false),
            'nombre_avis' => $nombreAvis,
            'nombre_avis_libelle' => $this->formatterNombreAvis($nombreAvis),
            'lien_google_maps' => (string) ($detailsLieu['googleMapsUri'] ?? $lieuRecherche['googleMapsUri'] ?? ''),
            'tri_libelle' => 'Avis affichés par pertinence via Google.',
            'date_recuperation' => $dateRecuperation,
            'date_recuperation_libelle' => $this->formatterDateRecuperation($dateRecuperation),
            'avis' => $avisNormalises,
            'message' => '',
            'source_cache' => 'direct',
            'place_id' => (string) ($detailsLieu['id'] ?? $lieuRecherche['id'] ?? ''),
        ];
    }

    private function construireInstantaneIndisponible(string $statut, string $message): array
    {
        return [
            'statut' => $statut,
            'nom_lieu' => '',
            'adresse' => '',
            'note_moyenne' => null,
            'note_moyenne_libelle' => '',
            'nombre_avis' => null,
            'nombre_avis_libelle' => '',
            'lien_google_maps' => '',
            'tri_libelle' => 'Avis affichés par pertinence via Google.',
            'date_recuperation' => gmdate('c'),
            'date_recuperation_libelle' => '',
            'avis' => [],
            'message' => $message,
            'source_cache' => 'direct',
            'place_id' => '',
        ];
    }

    private function effectuerRequeteJson(
        string $url,
        string $methode = 'GET',
        ?array $chargeUtile = null,
        array $enTetesSupplementaires = []
    ): array {
        $enTetes = array_merge(
            [
                'Accept: application/json',
                'User-Agent: ' . $this->agentUtilisateur,
            ],
            $enTetesSupplementaires
        );

        $optionsHttp = [
            'method' => strtoupper($methode),
            'timeout' => 8,
            'ignore_errors' => true,
            'header' => implode("\r\n", $enTetes),
        ];

        if ($chargeUtile !== null) {
            $contenuJson = json_encode($chargeUtile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($contenuJson === false) {
                return [
                    'code_statut' => 0,
                    'donnees' => null,
                ];
            }

            $optionsHttp['content'] = $contenuJson;
        }

        $contexte = stream_context_create([
            'http' => $optionsHttp,
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $contenu = @file_get_contents($url, false, $contexte);
        $enTetesReponse = $http_response_header ?? [];
        $codeStatut = $this->extraireCodeStatut($enTetesReponse);
        $donnees = null;

        if (is_string($contenu) && trim($contenu) !== '') {
            $donneesDecodees = json_decode($contenu, true);
            $donnees = is_array($donneesDecodees) ? $donneesDecodees : null;
        }

        return [
            'code_statut' => $codeStatut,
            'donnees' => $donnees,
        ];
    }

    private function extraireCodeStatut(array $enTetesReponse): int
    {
        if ($enTetesReponse === []) {
            return 0;
        }

        $ligneStatut = (string) ($enTetesReponse[0] ?? '');

        if (preg_match('/\s(\d{3})\s/', $ligneStatut, $correspondances) === 1) {
            return (int) $correspondances[1];
        }

        return 0;
    }

    private function extraireTexteAvis(array $avis): string
    {
        $texteLocalise = (string) ($avis['text']['text'] ?? '');
        $texteOriginal = (string) ($avis['originalText']['text'] ?? '');

        return trim($texteLocalise !== '' ? $texteLocalise : $texteOriginal);
    }

    private function formatterNote(?float $note, bool $avecSuffixe): string
    {
        if ($note === null) {
            return '';
        }

        $nombreDecimales = abs($note - round($note)) < 0.05 ? 0 : 1;
        $libelle = number_format($note, $nombreDecimales, ',', ' ');

        return $avecSuffixe ? $libelle . '/5' : $libelle;
    }

    private function formatterNombreAvis(?int $nombreAvis): string
    {
        if ($nombreAvis === null) {
            return '';
        }

        return $nombreAvis . ' avis';
    }

    private function formatterDateRecuperation(string $dateIso): string
    {
        $horodatage = strtotime($dateIso);

        if ($horodatage === false) {
            return '';
        }

        return 'Mis à jour le ' . gmdate('d/m/Y à H:i', $horodatage) . ' UTC';
    }

    private function versEntierNullable(mixed $valeur): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (!is_numeric($valeur)) {
            return null;
        }

        return (int) $valeur;
    }

    private function versFlottantNullable(mixed $valeur): ?float
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (!is_numeric($valeur)) {
            return null;
        }

        return (float) $valeur;
    }

    private function normaliserIdentifiantCache(string $identifiant): string
    {
        $identifiantNettoye = preg_replace('/[^a-z0-9_-]+/i', '-', trim($identifiant)) ?: 'lieu';

        return trim($identifiantNettoye, '-');
    }

    private function cheminCache(string $identifiant): string
    {
        return rtrim($this->dossierCache, '/\\') . DIRECTORY_SEPARATOR . $identifiant . '.json';
    }

    private function lireCache(string $identifiant): ?array
    {
        $cheminCache = $this->cheminCache($identifiant);

        if (!is_file($cheminCache)) {
            return null;
        }

        $contenu = @file_get_contents($cheminCache);

        if (!is_string($contenu) || trim($contenu) === '') {
            return null;
        }

        $donnees = json_decode($contenu, true);

        if (!is_array($donnees)) {
            return null;
        }

        $expireLe = (int) ($donnees['_expire_le'] ?? 0);
        $instantane = $donnees['instantane'] ?? null;

        if ($expireLe < time() || !is_array($instantane)) {
            return null;
        }

        $instantane['source_cache'] = 'cache';

        return $instantane;
    }

    private function ecrireCache(string $identifiant, array $instantane, int $dureeSecondes): void
    {
        $cheminCache = $this->cheminCache($identifiant);
        $chargeUtile = [
            '_expire_le' => time() + $dureeSecondes,
            'instantane' => $instantane,
        ];

        file_put_contents(
            $cheminCache,
            json_encode($chargeUtile, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private function ajouterAliasCompatibilite(array $instantane): array
    {
        return [
            ...$instantane,
            'status' => $instantane['statut'] ?? '',
            'place_name' => $instantane['nom_lieu'] ?? '',
            'address' => $instantane['adresse'] ?? '',
            'rating' => $instantane['note_moyenne'] ?? null,
            'rating_label' => $instantane['note_moyenne_libelle'] ?? '',
            'review_count' => $instantane['nombre_avis'] ?? null,
            'review_count_label' => $instantane['nombre_avis_libelle'] ?? '',
            'maps_url' => $instantane['lien_google_maps'] ?? '',
            'sort_label' => $instantane['tri_libelle'] ?? '',
            'reviews' => $instantane['avis'] ?? [],
            'updated_at_label' => $instantane['date_recuperation_libelle'] ?? '',
        ];
    }
}
