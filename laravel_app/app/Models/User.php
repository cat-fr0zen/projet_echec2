<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class User extends Model
{
    public const ROLE_CONNECTE = 'connecte';
    public const ROLE_ADHERENT = 'adherent';
    public const ROLE_ADMIN = 'admin';

    public const STATUT_COMPTE_ACTIF = 'actif';
    public const STATUT_COMPTE_SUSPENDU = 'suspendu';

    public const STATUT_ADHESION_AUCUNE = 'aucune';
    public const STATUT_ADHESION_ACTIVE = 'active';

    protected $table = 'compte_membre';
    protected $primaryKey = 'identifiant';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
