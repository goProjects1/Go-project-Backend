<?php

namespace App\Services;

use App\Mail\TripDecline;
use App\Mail\TripNotification;
use App\Models\Property;
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
    public function notifyUsersAndSaveTrip(Trip $trip): void
    {
        $this->logUsersWithinDistance($trip);

        // Send trip notifications to users within distance
        $this->sendTripNotifications($trip);
    }

    private function logUsersWithinDistance(Trip $trip)
    {
        $usersWithinDistance = $this->getUsersWithinDistance($trip);
        // Log users within distance
        Log::info('Users within distance for trip ' . $trip->id . ': ' . $usersWithinDistance->toJson());
    }

    private function sendTripNotifications(Trip $trip)
    {
        $usersWithinDistance = $this->getUsersWithinDistance($trip);

        foreach ($usersWithinDistance as $user) {
            try {https://go-project-ashy.vercel.app/account/trip_invite?tripId=8&action=decline
                $inviteLink = 'https://go-project-ashy.vercel.app/account/trip_invite?tripId=' . $trip->id;
                if (isset($user->email) && is_string($user->email)) {
                    // Fetch property details for the trip
                    $property = Property::findOrFail($trip->property_id);
                    $name = Auth::user()->last_name;
                    $this->saveTripForUser($trip, $user->id);
                    // Send trip notification
                    Mail::to($user->email)->send(new TripMail($trip, $inviteLink, $property->registration_no, $property->type, $name));
                    Log::info("Invitation sent to {$user->email} for trip {$trip->id}");

                    // Save the trip for the user

                }
            } catch (\Exception $e) {
                Log::error("Failed to send invitation to {$user->email} for trip {$trip->id}: {$e->getMessage()}");
            }
        }
    }

    private function saveTripForUser(Trip $trip, $userId)
    {
        // Find the user by email address
        $user = User::where('id', $userId)->first();

        if ($user) {
            $newTrip = clone $trip;
            // Set the user ID for the new trip
            $newTrip->guest_id = $user->id;
            // Save the new trip for the user
            $newTrip->save();
        } else {
            Log::error("User with email {$userId} not found.");
        }
    }

    private function getUsersWithinDistance(Trip $trip): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        if (!$user || !$user->latitude || !$user->longitude) {
            return collect();
        }

        $variableDistance = $trip->variable_distance;

        $usersWithinDistance = User::selectRaw(
            'users.*, ( 3959 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
            [$user->latitude, $user->longitude, $user->latitude]
        )
            ->having('distance', '<=', $variableDistance)
            ->get();

        return $usersWithinDistance;
    }

    public function acceptTrip(Trip $trip): bool
    {
        $userId = Auth::id();

        if ($userId) {
            $trip->update([
                'trip_status' => 'accepted',
                'available_seat' => $trip->available_seat - 1,
                'guest_id' => $userId,
            ]);

            $this->notifyTripCreator($trip);

            return true;
        } else {
            return false;
        }

    }
    protected function notifyTripCreator(Trip $trip)
    {
        Mail::to($trip->sender->email)->send(new TripNotification($trip));
    }

    protected function notifyTripCreatorForDecline(Trip $trip)
    {
        Mail::to($trip->sender->email)->send(new TripDecline($trip));
    }
    public function declineTrip(Trip $trip): bool
    {
        $userId = Auth::id();

        if ($userId) {
            $trip->update([
                'trip_status' => 'decline',
                'guest_id' => $userId,
            ]);

            $this->notifyTripCreatorForDecline($trip);

            return true;
        } else {
            return false;
        }

    }
}
