<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HoraireCreneau extends Model
{
    protected $table = 'horaire_creneau';
    protected $primaryKey = 'identifiant_creneau';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
