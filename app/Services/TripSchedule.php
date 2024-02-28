<?php

namespace App\Services;

//use App\Models\;


/**
 * @method static create($data)
 * @method static findOrFail($id)
 * @method static where(string $string, mixed $getAuthIdentifier)
 */
class TripSchedule
{
    public function createTripSchedule($data)
    {
        return TripSchedule::create($data);
    }
}
