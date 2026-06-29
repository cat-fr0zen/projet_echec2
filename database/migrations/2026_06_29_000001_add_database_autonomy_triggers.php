<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : add database autonomy triggers.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TRIGGERS = [
        'trg_compte_membre_autonomie_insert',
        'trg_compte_membre_autonomie_update',
        'trg_commande_locale_adhesion_insert',
        'trg_commande_locale_adhesion_update',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('compte_membre') || ! Schema::hasTable('commande_locale')) {
            return;
        }

        $this->supprimerTriggers();

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->creerTriggersSqlite();

            return;
        }

        if ($driver === 'mysql') {
            $this->creerTriggersMysqlAvecRepli();
        }
    }

    public function down(): void
    {
        $this->supprimerTriggers();
    }

    private function supprimerTriggers(): void
    {
        foreach (self::TRIGGERS as $triggerName) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$triggerName}");
        }
    }

    private function creerTriggersSqlite(): void
    {
        $saisonCourante = $this->expressionSaisonSqlite();
        $categorieAdhesion = $this->expressionCategorieAdhesionSqlite('NEW.categorie');

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_compte_membre_autonomie_insert
            AFTER INSERT ON compte_membre
            FOR EACH ROW
            WHEN (
                NEW.code_statut_adhesion = 'active'
                AND (
                    NEW.code_role = 'connecte'
                    OR COALESCE(TRIM(NEW.saison_adhesion_active), '') = ''
                    OR COALESCE(TRIM(NEW.saison_relance_adhesion), '') <> ''
                    OR NEW.adhesion_renouvelee_le IS NULL
                )
            ) OR (
                NEW.code_statut_adhesion = 'aucune'
                AND (
                    NEW.code_role = 'adherent'
                    OR COALESCE(TRIM(NEW.saison_adhesion_active), '') <> ''
                )
            )
            BEGIN
                UPDATE compte_membre
                SET
                    code_role = CASE
                        WHEN code_statut_adhesion = 'active' AND code_role = 'connecte' THEN 'adherent'
                        WHEN code_statut_adhesion = 'aucune' AND code_role = 'adherent' THEN 'connecte'
                        ELSE code_role
                    END,
                    saison_adhesion_active = CASE
                        WHEN code_statut_adhesion = 'active' THEN {$saisonCourante}
                        WHEN code_statut_adhesion = 'aucune' THEN NULL
                        ELSE saison_adhesion_active
                    END,
                    saison_relance_adhesion = CASE
                        WHEN code_statut_adhesion = 'active' THEN NULL
                        ELSE saison_relance_adhesion
                    END,
                    adhesion_renouvelee_le = CASE
                        WHEN code_statut_adhesion = 'active' THEN COALESCE(adhesion_renouvelee_le, CURRENT_TIMESTAMP)
                        ELSE adhesion_renouvelee_le
                    END,
                    mis_a_jour_le = CURRENT_TIMESTAMP
                WHERE identifiant = NEW.identifiant;
            END
            SQL
        );

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_compte_membre_autonomie_update
            AFTER UPDATE OF code_role, code_statut_adhesion, saison_adhesion_active, saison_relance_adhesion, adhesion_renouvelee_le
            ON compte_membre
            FOR EACH ROW
            WHEN (
                NEW.code_statut_adhesion = 'active'
                AND (
                    NEW.code_role = 'connecte'
                    OR COALESCE(TRIM(NEW.saison_adhesion_active), '') = ''
                    OR COALESCE(TRIM(NEW.saison_relance_adhesion), '') <> ''
                    OR NEW.adhesion_renouvelee_le IS NULL
                )
            ) OR (
                NEW.code_statut_adhesion = 'aucune'
                AND (
                    NEW.code_role = 'adherent'
                    OR COALESCE(TRIM(NEW.saison_adhesion_active), '') <> ''
                )
            )
            BEGIN
                UPDATE compte_membre
                SET
                    code_role = CASE
                        WHEN code_statut_adhesion = 'active' AND code_role = 'connecte' THEN 'adherent'
                        WHEN code_statut_adhesion = 'aucune' AND code_role = 'adherent' THEN 'connecte'
                        ELSE code_role
                    END,
                    saison_adhesion_active = CASE
                        WHEN code_statut_adhesion = 'active' THEN {$saisonCourante}
                        WHEN code_statut_adhesion = 'aucune' THEN NULL
                        ELSE saison_adhesion_active
                    END,
                    saison_relance_adhesion = CASE
                        WHEN code_statut_adhesion = 'active' THEN NULL
                        ELSE saison_relance_adhesion
                    END,
                    adhesion_renouvelee_le = CASE
                        WHEN code_statut_adhesion = 'active' THEN COALESCE(adhesion_renouvelee_le, CURRENT_TIMESTAMP)
                        ELSE adhesion_renouvelee_le
                    END,
                    mis_a_jour_le = CURRENT_TIMESTAMP
                WHERE identifiant = NEW.identifiant;
            END
            SQL
        );

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_commande_locale_adhesion_insert
            AFTER INSERT ON commande_locale
            FOR EACH ROW
            WHEN NEW.code_statut = 'validee' AND {$categorieAdhesion}
            BEGIN
                UPDATE compte_membre
                SET
                    code_statut_adhesion = 'active',
                    code_role = CASE WHEN code_role = 'connecte' THEN 'adherent' ELSE code_role END,
                    saison_adhesion_active = {$saisonCourante},
                    saison_relance_adhesion = NULL,
                    adhesion_renouvelee_le = CURRENT_TIMESTAMP,
                    mis_a_jour_le = CURRENT_TIMESTAMP
                WHERE identifiant = NEW.identifiant_utilisateur
                  AND code_statut_compte = 'actif';
            END
            SQL
        );

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_commande_locale_adhesion_update
            AFTER UPDATE OF code_statut, categorie ON commande_locale
            FOR EACH ROW
            WHEN NEW.code_statut = 'validee' AND {$categorieAdhesion}
            BEGIN
                UPDATE compte_membre
                SET
                    code_statut_adhesion = 'active',
                    code_role = CASE WHEN code_role = 'connecte' THEN 'adherent' ELSE code_role END,
                    saison_adhesion_active = {$saisonCourante},
                    saison_relance_adhesion = NULL,
                    adhesion_renouvelee_le = CURRENT_TIMESTAMP,
                    mis_a_jour_le = CURRENT_TIMESTAMP
                WHERE identifiant = NEW.identifiant_utilisateur
                  AND code_statut_compte = 'actif';
            END
            SQL
        );
    }

    private function creerTriggersMysql(): void
    {
        $saisonCourante = $this->expressionSaisonMysql();
        $categorieAdhesion = $this->expressionCategorieAdhesionMysql('NEW.categorie');

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_compte_membre_autonomie_insert
            BEFORE INSERT ON compte_membre
            FOR EACH ROW
            BEGIN
                IF NEW.code_statut_adhesion = 'active' THEN
                    IF NEW.code_role = 'connecte' THEN
                        SET NEW.code_role = 'adherent';
                    END IF;

                    SET NEW.saison_adhesion_active = {$saisonCourante};
                    SET NEW.saison_relance_adhesion = NULL;
                    SET NEW.adhesion_renouvelee_le = COALESCE(NEW.adhesion_renouvelee_le, CURRENT_TIMESTAMP);
                ELSEIF NEW.code_statut_adhesion = 'aucune' THEN
                    IF NEW.code_role = 'adherent' THEN
                        SET NEW.code_role = 'connecte';
                    END IF;

                    SET NEW.saison_adhesion_active = NULL;
                END IF;
            END
            SQL
        );

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_compte_membre_autonomie_update
            BEFORE UPDATE ON compte_membre
            FOR EACH ROW
            BEGIN
                IF NEW.code_statut_adhesion = 'active' THEN
                    IF NEW.code_role = 'connecte' THEN
                        SET NEW.code_role = 'adherent';
                    END IF;

                    SET NEW.saison_adhesion_active = {$saisonCourante};
                    SET NEW.saison_relance_adhesion = NULL;
                    SET NEW.adhesion_renouvelee_le = COALESCE(NEW.adhesion_renouvelee_le, CURRENT_TIMESTAMP);
                ELSEIF NEW.code_statut_adhesion = 'aucune' THEN
                    IF NEW.code_role = 'adherent' THEN
                        SET NEW.code_role = 'connecte';
                    END IF;

                    SET NEW.saison_adhesion_active = NULL;
                END IF;
            END
            SQL
        );

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_commande_locale_adhesion_insert
            AFTER INSERT ON commande_locale
            FOR EACH ROW
            BEGIN
                IF NEW.code_statut = 'validee' AND {$categorieAdhesion} THEN
                    UPDATE compte_membre
                    SET
                        code_statut_adhesion = 'active',
                        code_role = CASE WHEN code_role = 'connecte' THEN 'adherent' ELSE code_role END,
                        saison_adhesion_active = {$saisonCourante},
                        saison_relance_adhesion = NULL,
                        adhesion_renouvelee_le = CURRENT_TIMESTAMP,
                        mis_a_jour_le = CURRENT_TIMESTAMP
                    WHERE identifiant = NEW.identifiant_utilisateur
                      AND code_statut_compte = 'actif';
                END IF;
            END
            SQL
        );

        DB::unprepared(
            <<<SQL
            CREATE TRIGGER trg_commande_locale_adhesion_update
            AFTER UPDATE ON commande_locale
            FOR EACH ROW
            BEGIN
                IF NEW.code_statut = 'validee' AND {$categorieAdhesion} THEN
                    UPDATE compte_membre
                    SET
                        code_statut_adhesion = 'active',
                        code_role = CASE WHEN code_role = 'connecte' THEN 'adherent' ELSE code_role END,
                        saison_adhesion_active = {$saisonCourante},
                        saison_relance_adhesion = NULL,
                        adhesion_renouvelee_le = CURRENT_TIMESTAMP,
                        mis_a_jour_le = CURRENT_TIMESTAMP
                    WHERE identifiant = NEW.identifiant_utilisateur
                      AND code_statut_compte = 'actif';
                END IF;
            END
            SQL
        );
    }

    /**
     * Sur certains hebergeurs MySQL, la creation de trigger est interdite sans
     * privilege SUPER ou sans l'option log_bin_trust_function_creators.
     * Dans ce cas, on laisse la migration se terminer pour ne pas bloquer
     * tout le site : la logique applicative Laravel reste alors la securite
     * principale a la place de l'autonomie base 100 %.
     */
    private function creerTriggersMysqlAvecRepli(): void
    {
        try {
            $this->creerTriggersMysql();
        } catch (QueryException $exception) {
            if (! $this->estErreurPrivilegeTriggerMysql($exception)) {
                throw $exception;
            }

            $this->supprimerTriggers();
            $this->afficherAvertissementTriggersMysql($exception);
        }
    }

    private function estErreurPrivilegeTriggerMysql(QueryException $exception): bool
    {
        $sqlState = strtoupper((string) ($exception->errorInfo[0] ?? ''));
        $codeErreur = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        if ($sqlState === 'HY000' && $codeErreur === 1419) {
            return true;
        }

        return str_contains($message, 'super privilege')
            || str_contains($message, 'log_bin_trust_function_creators');
    }

    private function afficherAvertissementTriggersMysql(QueryException $exception): void
    {
        $message = '[migration] Triggers MySQL d autonomie ignores sur cet environnement : '
            .'privileges insuffisants pour CREATE TRIGGER. '
            .'Le site continue avec la logique Laravel, mais l autonomie base pure reste desactivee.'
            .' Detail: '.$exception->getCode();

        if (defined('STDOUT')) {
            fwrite(STDOUT, $message.PHP_EOL);
        }
    }

    private function expressionSaisonSqlite(): string
    {
        return "CASE
            WHEN CAST(strftime('%m', 'now') AS INTEGER) >= 9
                THEN printf('%04d-%04d', CAST(strftime('%Y', 'now') AS INTEGER), CAST(strftime('%Y', 'now') AS INTEGER) + 1)
            ELSE printf('%04d-%04d', CAST(strftime('%Y', 'now') AS INTEGER) - 1, CAST(strftime('%Y', 'now') AS INTEGER))
        END";
    }

    private function expressionSaisonMysql(): string
    {
        return "CASE
            WHEN MONTH(CURRENT_DATE()) >= 9
                THEN CONCAT(YEAR(CURRENT_DATE()), '-', YEAR(CURRENT_DATE()) + 1)
            ELSE CONCAT(YEAR(CURRENT_DATE()) - 1, '-', YEAR(CURRENT_DATE()))
        END";
    }

    private function expressionCategorieAdhesionSqlite(string $expression): string
    {
        return "REPLACE(LOWER(TRIM(COALESCE({$expression}, ''))), 'é', 'e') = 'adhesion'";
    }

    private function expressionCategorieAdhesionMysql(string $expression): string
    {
        return "REPLACE(LOWER(TRIM(COALESCE({$expression}, ''))), 'é', 'e') = 'adhesion'";
    }
};
