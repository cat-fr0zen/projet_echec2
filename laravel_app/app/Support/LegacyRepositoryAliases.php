<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Article as ArticleModel;
use App\Models\CommandeLocale as CommandeLocaleModel;
use App\Models\MediaPublication as MediaPublicationModel;
use App\Models\User as UserModel;

final class DepotUtilisateurs
{
    public const ROLE_CONNECTE = UserModel::ROLE_CONNECTE;
    public const ROLE_ADHERENT = UserModel::ROLE_ADHERENT;
    public const ROLE_ADMIN = UserModel::ROLE_ADMIN;

    public const STATUT_COMPTE_ACTIF = UserModel::STATUT_COMPTE_ACTIF;
    public const STATUT_COMPTE_SUSPENDU = UserModel::STATUT_COMPTE_SUSPENDU;

    public const STATUT_ADHESION_AUCUNE = UserModel::STATUT_ADHESION_AUCUNE;
    public const STATUT_ADHESION_ACTIVE = UserModel::STATUT_ADHESION_ACTIVE;
}

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

final class DepotMedias
{
    public const TYPE_PHOTO = MediaPublicationModel::TYPE_PHOTO;
    public const TYPE_VIDEO = MediaPublicationModel::TYPE_VIDEO;

    public const STATUT_EN_ATTENTE = MediaPublicationModel::STATUT_EN_ATTENTE;
    public const STATUT_PUBLIE = MediaPublicationModel::STATUT_PUBLIE;
    public const STATUT_REFUSE = MediaPublicationModel::STATUT_REFUSE;
}

final class DepotCommandes
{
    public const STATUT_EN_ATTENTE = CommandeLocaleModel::STATUT_EN_ATTENTE;
    public const STATUT_VALIDEE = CommandeLocaleModel::STATUT_VALIDEE;
    public const STATUT_ANNULEE = CommandeLocaleModel::STATUT_ANNULEE;
}
