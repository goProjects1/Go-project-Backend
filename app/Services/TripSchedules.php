<?php

namespace App\Services;

use App\Models\TripSchedule;


/**
 * @method static create($data)
 * @method static findOrFail($id)
 * @method static where(string $string, mixed $getAuthIdentifier)
 */
class TripSchedules
{
    public function createTripSchedule($data)
    {
        return TripSchedule::create($data);
    }
}
