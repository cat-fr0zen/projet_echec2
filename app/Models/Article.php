<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : Article.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Article extends Model
{
    public const STATUT_EN_ATTENTE = 'en_attente_validation';
    public const STATUT_PUBLIE = 'publie';
    public const STATUT_REFUSE = 'refuse';

    public const TYPE_BLOC_PARAGRAPHE = 'paragraphe';
    public const TYPE_BLOC_SOUS_TITRE = 'sous_titre';
    public const TYPE_BLOC_IMAGE = 'image';
    public const TYPE_BLOC_VIDEO = 'video';

    protected $table = 'article';
    protected $primaryKey = 'identifiant';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
