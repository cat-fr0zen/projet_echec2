<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class NewsletterEnvoi extends Model
{
    protected $table = 'newsletter_envoi';
    protected $primaryKey = 'identifiant_envoi';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
