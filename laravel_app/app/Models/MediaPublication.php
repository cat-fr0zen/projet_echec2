<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MediaPublication extends Model
{
    public const TYPE_PHOTO = 'photo';
    public const TYPE_VIDEO = 'video';

    public const STATUT_EN_ATTENTE = 'en_attente_validation';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_REFUSE = 'refuse';

    protected $table = 'media_publication';
    protected $primaryKey = 'identifiant';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
