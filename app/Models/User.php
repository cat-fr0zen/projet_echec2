<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPasswordTrait;

    public const ROLE_CONNECTE = 'connecte';

    public const ROLE_ADHERENT = 'adherent';

    public const ROLE_PROF = 'prof';

    public const ROLE_ADMIN = 'admin';

    public const MAX_PROFESSEURS = 10;

    public const STATUT_COMPTE_ACTIF = 'actif';

    public const STATUT_COMPTE_SUSPENDU = 'suspendu';

    public const STATUT_ADHESION_AUCUNE = 'aucune';

    public const STATUT_ADHESION_ACTIVE = 'active';

    protected $table = 'compte_membre';

    protected $primaryKey = 'identifiant';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $hidden = ['mot_de_passe_hache'];

    public function getAuthPassword(): string
    {
        return (string) $this->mot_de_passe_hache;
    }

    public function getEmailForPasswordReset(): string
    {
        return (string) $this->courriel;
    }
}
