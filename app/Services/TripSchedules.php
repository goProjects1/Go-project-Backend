<?php

namespace App\Services;

use App\Models\TripSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;


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
        // Validate inputs
        if (is_null($latitude) || is_null($longitude)) {
            throw new \InvalidArgumentException('Latitude and longitude are required.');
        }
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


    public function acceptScheduleTrip(Request $request, $scheduleTripId)
    {
        $tripSchedule = TripSchedule::findOrFail($scheduleTripId);

        if ($tripSchedule->allowUserMeetingPoint) {
            $tripScheduleData['meeting_point'] = $request->meeting_point;
        } else {
            $tripScheduleData['meeting_point'] = $tripSchedule->pickUp;
        }

        $tripScheduleData['schedule_journey_status'] = "waiting";
        $tripScheduleData['destination'] = $request->destination;
        $tripScheduleData['sourceLatitude'] = $request->sourceLatitude;
        $tripScheduleData['sourceLongitude'] = $request->sourceLongitude;
        $tripScheduleData['destLatitude'] = $request->destLatitude;
        $tripScheduleData['destLongitude'] = $request->destLongitude;
        $tripScheduleData['schedule_trip_id'] = $scheduleTripId;

        if ($tripSchedule->usertype == 'driver') {
            $tripScheduleData['driver_id'] = Auth::id();
        } elseif ($tripSchedule->usertype == 'passenger') {
            $tripScheduleData['passenger_id'] = Auth::id();
        }

        // Save the trip schedule data
        $tripScheduleRequest =  TripScheduleActive::create($tripScheduleData);
        // Send email notification
        $user = User::find($tripSchedule->user_id);
        Mail::send('emails.trip-schedule', ['tripSchedule' => $tripScheduleRequest], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Schedule Trip Request');
        });

        return $tripScheduleData;
    }
}
