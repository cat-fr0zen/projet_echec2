<?php

declare(strict_types=1);

/**
 * Connexion Oracle via OCI8.
 *
 * Toutes les requetes passent par des variables liees pour eviter les injections.
 */
final class BaseDeDonneesOracle
{
    /** @var resource|null */
    private $connexion = null;

    private bool $transactionActive = false;

    private function __construct(
        private string $chaineConnexion,
        private string $utilisateur,
        private string $motDePasse,
        private string $charset
    ) {
    }

    public static function depuisEnvironnement(): self
    {
        $hote = self::envObligatoire('ORACLE_HOST');
        $port = getenv('ORACLE_PORT');
        $service = self::envObligatoire('ORACLE_SERVICE');
        $utilisateur = self::envObligatoire('ORACLE_USER');
        $motDePasse = self::envObligatoire('ORACLE_PASSWORD');
        $charset = getenv('ORACLE_CHARSET');

        $chaineConnexion = sprintf('//%s:%s/%s', $hote, $port !== false && $port !== '' ? $port : '1521', $service);

        return new self(
            $chaineConnexion,
            $utilisateur,
            $motDePasse,
            $charset !== false && $charset !== '' ? $charset : 'AL32UTF8'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lireTout(string $sql, array $parametres = []): array
    {
        $statement = $this->preparerEtExecuter($sql, $parametres);
        $lignes = [];

        while (($ligne = oci_fetch_assoc($statement)) !== false) {
            $lignes[] = array_change_key_case($ligne, CASE_LOWER);
        }

        oci_free_statement($statement);

        return $lignes;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lireUne(string $sql, array $parametres = []): ?array
    {
        $lignes = $this->lireTout($sql, $parametres);

        return $lignes[0] ?? null;
    }

    public function executer(string $sql, array $parametres = []): int
    {
        $statement = $this->preparerEtExecuter($sql, $parametres);
        $lignesAffectees = oci_num_rows($statement);
        oci_free_statement($statement);

        if (!$this->transactionActive) {
            oci_commit($this->obtenirConnexion());
        }

        return $lignesAffectees;
    }

    public function transaction(callable $operation): mixed
    {
        $dejaDansTransaction = $this->transactionActive;
        $this->transactionActive = true;

        try {
            $resultat = $operation($this);

            if (!$dejaDansTransaction) {
                oci_commit($this->obtenirConnexion());
            }

            return $resultat;
        } catch (Throwable $exception) {
            if (!$dejaDansTransaction) {
                oci_rollback($this->obtenirConnexion());
            }

            throw $exception;
        } finally {
            $this->transactionActive = $dejaDansTransaction;
        }
    }

    private static function envObligatoire(string $nom): string
    {
        $valeur = getenv($nom);

        if ($valeur === false || trim($valeur) === '') {
            throw new RuntimeException('Configuration Oracle manquante: ' . $nom);
        }

        return trim($valeur);
    }

    /**
     * @return resource
     */
    private function obtenirConnexion()
    {
        if ($this->connexion !== null) {
            return $this->connexion;
        }

        if (!function_exists('oci_connect')) {
            throw new RuntimeException("Extension PHP oci8 absente. Active oci8 dans XAMPP avant d'utiliser Oracle.");
        }

        $connexion = @oci_connect($this->utilisateur, $this->motDePasse, $this->chaineConnexion, $this->charset);

        if ($connexion === false) {
            $this->leverErreurOracle(null, 'Connexion Oracle impossible.');
        }

        $this->connexion = $connexion;

        return $this->connexion;
    }

    /**
     * @return resource
     */
    private function preparerEtExecuter(string $sql, array $parametres)
    {
        $statement = @oci_parse($this->obtenirConnexion(), $sql);

        if ($statement === false) {
            $this->leverErreurOracle($this->obtenirConnexion(), 'Preparation de requete Oracle impossible.');
        }

        $valeursLiees = [];

        foreach ($parametres as $nom => $valeur) {
            $placeholder = ':' . ltrim((string) $nom, ':');
            $valeursLiees[$placeholder] = $valeur;
            $taille = is_string($valeur) ? max(strlen($valeur), 1) : -1;

            if (!oci_bind_by_name($statement, $placeholder, $valeursLiees[$placeholder], $taille)) {
                $this->leverErreurOracle($statement, 'Liaison de parametre Oracle impossible.');
            }
        }

        $mode = $this->transactionActive ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS;

        if (!@oci_execute($statement, $mode)) {
            $this->leverErreurOracle($statement, 'Execution de requete Oracle impossible.');
        }

        return $statement;
    }

    /**
     * @param resource|null $ressource
     * @return never
     */
    private function leverErreurOracle($ressource, string $message): never
    {
        $erreur = $ressource !== null ? oci_error($ressource) : oci_error();
        $detail = is_array($erreur) && isset($erreur['message']) ? (string) $erreur['message'] : 'erreur inconnue';

        throw new RuntimeException($message . ' ' . $detail);
    }
}
