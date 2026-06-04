<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User as UserModel;

final class DepotUtilisateurs
{
    public const ROLE_CONNECTE = UserModel::ROLE_CONNECTE;
    public const ROLE_ADHERENT = UserModel::ROLE_ADHERENT;
    public const ROLE_PROF = UserModel::ROLE_PROF;
    public const ROLE_ADMIN = UserModel::ROLE_ADMIN;
    public const MAX_PROFESSEURS = UserModel::MAX_PROFESSEURS;

    public const STATUT_COMPTE_ACTIF = UserModel::STATUT_COMPTE_ACTIF;
    public const STATUT_COMPTE_SUSPENDU = UserModel::STATUT_COMPTE_SUSPENDU;

    public const STATUT_ADHESION_AUCUNE = UserModel::STATUT_ADHESION_AUCUNE;
    public const STATUT_ADHESION_ACTIVE = UserModel::STATUT_ADHESION_ACTIVE;
}
