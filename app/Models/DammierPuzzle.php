<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class DammierPuzzle extends Model
{
    protected $table = 'dammier_puzzle';
    protected $primaryKey = 'dammier_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
