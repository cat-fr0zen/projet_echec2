<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : DammierScore.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class DammierScore extends Model
{
    protected $table = 'dammier_score';
    protected $primaryKey = 'dammier_score_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
