<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class HoraireClub extends Model
{
    protected $table = 'horaire_club';
    protected $primaryKey = 'schedule_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
