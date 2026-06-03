<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Article as ArticleModel;

final class DepotArticles
{
    public const STATUT_EN_ATTENTE = ArticleModel::STATUT_EN_ATTENTE;
    public const STATUT_PUBLIE = ArticleModel::STATUT_PUBLIE;
    public const STATUT_REFUSE = ArticleModel::STATUT_REFUSE;

    public const TYPE_BLOC_PARAGRAPHE = ArticleModel::TYPE_BLOC_PARAGRAPHE;
    public const TYPE_BLOC_SOUS_TITRE = ArticleModel::TYPE_BLOC_SOUS_TITRE;
    public const TYPE_BLOC_IMAGE = ArticleModel::TYPE_BLOC_IMAGE;
    public const TYPE_BLOC_VIDEO = ArticleModel::TYPE_BLOC_VIDEO;
}
