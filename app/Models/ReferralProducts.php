<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $user_id
 * @property mixed $service
 * @property mixed $point
 */
class ReferralProducts extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'point',
        'user_id'

    ];
}
