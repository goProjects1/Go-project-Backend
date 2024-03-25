<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class Referral_By extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ref_code',
    ];
}
