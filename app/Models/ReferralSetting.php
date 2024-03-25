<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static whereNotNull(string $string)
 * @method static where(string $string, string $string1)
 * @method static create(array $array)
 * @method static find($referralId)
 */
class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'point_limit',
        'point_conversion',
        'status',
        'end_date',
        'start_date'
    ];
}
