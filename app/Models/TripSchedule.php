<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create($data)
 * @method static findOrFail($id)
 * @method static where(string $string, mixed $getAuthIdentifier)
 */
class TripSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickUp',
        'destination',
        'variable_distance',
        'description',
        'to_time',
        'frequency',
        'user_id',
        'plan_time',
        'amount',
        'pay_option',
        'usertype',
    ];
}
