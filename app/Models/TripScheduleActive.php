<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripScheduleActive extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination',
        'meeting_point',
        'driver_id',
        'passenger_id',
        'schedule_trip_id',
        'schedule_journey_status',
        'sourceLatitude',
        'sourceLongitude',
        'destLatitude',
        'destLongitude',
    ];
}
