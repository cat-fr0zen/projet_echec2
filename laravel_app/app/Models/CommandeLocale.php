<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommandeLocale extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE = 'validee';
    public const STATUT_ANNULEE = 'annulee';

    protected $table = 'commande_locale';
    protected $primaryKey = 'identifiant';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
