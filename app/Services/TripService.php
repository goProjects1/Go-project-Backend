<?php

namespace App\Services;

use App\Models\Trip;
use App\Mail\TripMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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

    protected function getUsersWithinDistance(Trip $trip)
    {
        // Implement logic to fetch users within variable distance
        // Example: Retrieve users within a certain distance from the trip location

        // For illustration purposes, let's assume there is a User model with a 'location' attribute
        // Adjust this logic based on your actual user model and location data
        return User::where('location', '<=', $trip->variable_distance)->get();
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
