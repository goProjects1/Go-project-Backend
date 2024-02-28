<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripShedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'pickUp',
        'destination',
        'variable_distance',
        'description',
        'to_time',
        'from_time',
        'frequency',
        'user_id',
    ];
}
