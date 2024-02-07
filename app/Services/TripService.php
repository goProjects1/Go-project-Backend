<?php

namespace App\Services;

use App\Models\Trip;
use App\Mail\TripMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;


class TripService
{
    public function createTripAndNotifyUsers($tripData)
    {
        $trip = Trip::create($tripData);

        $this->sendTripNotifications($trip);

        return $trip;
    }

    protected function sendTripNotifications(Trip $trip)
    {
        $usersWithinDistance = $this->getUsersWithinDistance($trip);

        foreach ($usersWithinDistance as $user) {
            // Send trip details as email
            Mail::to($user->email)->send(new TripMail($trip));
        }
    }

    protected function getUsersWithinDistance(Trip $trip): \Illuminate\Http\JsonResponse
    {
        // Get the authenticated user
        $user = Auth::user();
        // Check if user's latitude and longitude are available
        if (!$user || !$user->latitude || !$user->longitude) {
            return response()->json(['error' => 'User location not available'], 400);
        }

        // Assuming you have a User model with 'latitude' and 'longitude' attributes
        $usersWithinDistance = User::selectRaw(
            '( 3959 * acos( cos( radians(?) ) * cos( radians( latitude ) )
                * cos( radians( longitude ) - radians(?) ) + sin( radians(?) )
                * sin( radians( latitude ) ) ) ) AS distance',
            [$user->latitude, $user->longitude, $user->latitude]
        )
            ->having('distance', '<=', 10) // 10 miles distance
            ->get();

        return response()->json(['users' => $usersWithinDistance]);
    }


    public function acceptTrip($trip, $userId): bool
    {
        $user = User::findOrFail($userId);

        if ($user->location <= $trip->variable_distance) {
            // Update trip status and reduce available seat
            $trip->update([
                'trip_status' => 'accepted',
                'available_seat' => $trip->available_seat - 1,
                'guess_id' => Auth::user()->getAuthIdentifier(),
            ]);

            // Notify the trip creator about the acceptance
            $this->notifyTripCreator($trip);

            return true; // Trip accepted successfully
        }

        return false; // User is not within the specified distance
    }

    protected function notifyTripCreator(Trip $trip)
    {
        // Notify the trip creator about the acceptance
        Mail::to($trip->sender->email)->send(new TripMail($trip));
    }
}
