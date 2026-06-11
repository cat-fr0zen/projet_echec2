<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CommandeLocale extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE = 'validee';
    public const STATUT_ANNULEE = 'annulee';
    public const MODE_PAIEMENT_SUR_PLACE = 'sur_place';
    public const MODE_PAIEMENT_CARTE_BANCAIRE = 'carte_bancaire';
    public const STATUT_PAIEMENT_A_FINALISER = 'a_finaliser';
    public const STATUT_PAIEMENT_EN_ATTENTE_PRESTATAIRE = 'en_attente_prestataire';
    public const STATUT_PAIEMENT_REGLE = 'regle';
    public const STATUT_PAIEMENT_ANNULE = 'annule';

    protected $table = 'commande_locale';
    protected $primaryKey = 'identifiant';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
