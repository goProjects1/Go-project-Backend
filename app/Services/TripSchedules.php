<?php

namespace App\Services;

use App\Models\TripSchedule;
use Illuminate\Support\Facades\Auth;


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


    public function getAllScheduledJourney($latitude, $longitude)
    {
        // Query the database for trip schedules where the requested latitude and longitude are within the latitude and longitude range
        return TripSchedule::where('schedule_status', 'active')
            ->where('latitude', '>=', $latitude)
            ->where('latitude', '<=', $latitude)
            ->where('longitude', '>=', $longitude)
            ->where('longitude', '<=', $longitude)
            ->get();
    }

    public function updateScheduleStatus($scheduleId, $newStatus): string
    {
        // Get the authenticated user
        $user = Auth::user();

        // Find the trip schedule by ID
        $schedule = TripSchedule::find($scheduleId);

        // Check if the schedule exists
        if (!$schedule) {
            return "Schedule not found.";
        }

        // Check if the authenticated user owns the trip schedule
        if ($user->id !== $schedule->user_id) {
            return "Unauthorized. You do not own this trip schedule.";
        }

        // Update the schedule status
        $schedule->schedule_status = $newStatus;
        $schedule->save();

        return "Schedule status updated successfully.";
    }
}
