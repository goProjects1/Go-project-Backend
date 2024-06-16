<?php

namespace App\Services;

use App\Models\TripSchedule;
use App\Models\TripScheduleActive;
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


    public function acceptScheduleTrip(Request $request, $scheduleTripId): \Illuminate\Http\JsonResponse
    {
        // Find the trip schedule by ID or fail
        $tripSchedule = TripSchedule::findOrFail($scheduleTripId);

        // Check if available seats are present and if they are fully booked
        if ($tripSchedule->available_seat !== null && $tripSchedule->available_seat <= 0) {
            return response()->json(['message' => 'Available seats are complete'], 400);
        }

        // Initialize trip schedule data array
        $tripScheduleData = [];

        // Determine the meeting point based on the trip schedule's allowUserMeetingPoint property
        if ($tripSchedule->allowUserMeetingPoint) {
            $tripScheduleData['meeting_point'] = $request->input('meeting_point');
        } else {
            $tripScheduleData['meeting_point'] = $tripSchedule->pickUp;
        }

        // Set the trip schedule data properties
        $tripScheduleData['schedule_journey_status'] = "waiting";
        $tripScheduleData['destination'] = $request->input('destination');
        $tripScheduleData['sourceLatitude'] = $request->input('sourceLatitude');
        $tripScheduleData['sourceLongitude'] = $request->input('sourceLongitude');
        $tripScheduleData['destLatitude'] = $request->input('destLatitude');
        $tripScheduleData['destLongitude'] = $request->input('destLongitude');
        $tripScheduleData['schedule_trip_id'] = $scheduleTripId;

        // Set the user type specific ID (driver or passenger)
        if ($tripSchedule->usertype == 'driver') {
            $tripScheduleData['driver_id'] = Auth::id();
        } elseif ($tripSchedule->usertype == 'passenger') {
            $tripScheduleData['passenger_id'] = Auth::id();
        }

        // Create a new TripScheduleActive record
        $tripScheduleRequest = TripScheduleActive::create($tripScheduleData);

        // Update the available seats
        if ($tripSchedule->available_seat !== null) {
            $tripSchedule->available_seat -= 1;
            $tripSchedule->save();
        }

        // Send email notification
        $user = User::find($tripSchedule->user_id);
        Mail::send('Email.trip_schedule', ['tripSchedule' => $tripScheduleRequest, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Schedule Trip Request');
        });

        // Return the trip schedule request as a JSON response
        return response()->json($tripScheduleRequest);
    }



}
