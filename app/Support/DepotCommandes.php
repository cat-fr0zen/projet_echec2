<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DepotCommandes.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\CommandeLocale as CommandeLocaleModel;

final class DepotCommandes
{
    public const STATUT_EN_ATTENTE = CommandeLocaleModel::STATUT_EN_ATTENTE;
    public const STATUT_VALIDEE = CommandeLocaleModel::STATUT_VALIDEE;
    public const STATUT_ANNULEE = CommandeLocaleModel::STATUT_ANNULEE;
}
