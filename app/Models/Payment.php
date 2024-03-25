<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [

        'user_id',
        'passenger_id',
        'variable_distance',
        'trip_id',
        'unique_code',
        'email',
        'split_method_id',
        'amount'
    ];
}
