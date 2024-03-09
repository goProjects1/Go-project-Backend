<?php

namespace App\Services;

use App\Models\Trip;
use App\Mail\TripMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;


class TripService
{
    public function createTripAndNotifyUsers(Trip $trip): Trip
    {
        $trip->save();

        $this->logUsersWithinDistance($trip);

        // Send trip notifications to users within distance
        $this->sendTripNotifications($trip);

        return $trip;
    }

    private function logUsersWithinDistance(Trip $trip)
    {
        $usersWithinDistance = $this->getUsersWithinDistance($trip);

        // Log users within distance
        Log::info('Users within distance for trip ' . $trip->id . ': ' . json_encode($usersWithinDistance));
    }

    private function sendTripNotifications(Trip $trip)
    {
        $usersWithinDistance = $this->getUsersWithinDistance($trip);

        foreach ($usersWithinDistance as $user) {
            if (isset($user->email) && is_string($user->email)) {
                Mail::to($user->email)->send(new TripMail($trip));
            }
        }
    }

    private function getUsersWithinDistance(Trip $trip): \Illuminate\Support\Collection
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if user's latitude and longitude are available
        if (!$user || !$user->latitude || !$user->longitude) {
            return collect();
        }

        $variableDistance = $trip->variable_distance;

        // Assuming you have a User model with 'latitude' and 'longitude' attributes
        $usersWithinDistance = User::selectRaw(
            'users.*, ( 3959 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
            [$user->latitude, $user->longitude, $user->latitude]
        )
            ->having('distance', '<=', $variableDistance)
            ->get();

        return $usersWithinDistance;
    }

//    private function saveDistanceForTrip(Trip $trip)
//    {
//        $usersWithinDistance = $this->getUsersWithinDistance($trip);
//
//        // Check if there are users within distance
//        if ($usersWithinDistance->count() > 0) {
//            // Get the average distance for users within distance
//            $averageDistance = $usersWithinDistance->avg('distance');
//
//            // Save the average distance to the trip table
//            $trip->update(['distance' => $averageDistance]);
//        } else {
//            // Set a default or placeholder value for distance
//            $trip->update(['distance' => null]); // You can use any default value or NULL as per your needs
//        }
//    }

    public function acceptTrip($trip, $userId): bool
    {
        $user = User::findOrFail($userId);

        // if ($user->distnace <= $trip->variable_distance) {
        // Update trip status and reduce available seat
        $trip->update([
            'trip_status' => 'accepted',
            'available_seat' => $trip->available_seat - 1,
            'guess_id' => Auth::user()->getAuthIdentifier(),
        ]);

        // Notify the trip creator about the acceptance
        $this->notifyTripCreator($trip);

        return true;
        // }

        // return false;
    }


    protected function notifyTripCreator(Trip $trip)
    {
        // Notify the trip creator about the acceptance
        Mail::to($trip->sender->email)->send(new TripMail($trip));
    }
}
