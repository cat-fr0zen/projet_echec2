<?php
/**
 * Fichier du projet. Role : participer au fonctionnement du site. Theme principal : NewsletterAbonnement.
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class NewsletterAbonnement extends Model
{
    protected $table = 'newsletter_abonnement';
    protected $primaryKey = 'identifiant_abonnement';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
