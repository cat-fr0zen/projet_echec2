<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DepotMedias.
 */

declare(strict_types=1);

namespace App\Support;

use App\Models\MediaPublication as MediaPublicationModel;

final class DepotMedias
{
    public const TYPE_PHOTO = MediaPublicationModel::TYPE_PHOTO;
    public const TYPE_VIDEO = MediaPublicationModel::TYPE_VIDEO;

    public const STATUT_EN_ATTENTE = MediaPublicationModel::STATUT_EN_ATTENTE;
    public const STATUT_PUBLIE = MediaPublicationModel::STATUT_PUBLIE;
    public const STATUT_REFUSE = MediaPublicationModel::STATUT_REFUSE;
}
