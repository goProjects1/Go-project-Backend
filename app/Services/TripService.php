<?php

namespace App\Services;

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
        Log::info('Users within distance for trip ' . $trip->id . ': ' . $usersWithinDistance->toJson());
    }

    private function sendTripNotifications(Trip $trip)
    {
        $usersWithinDistance = $this->getUsersWithinDistance($trip);

        foreach ($usersWithinDistance as $user) {
            try {
                $inviteLink = 'http://127.0.0.1:8000/trip_invite?tripId=' . $trip->id;
                if (isset($user->email) && is_string($user->email)) {
                    // Fetch property details for the trip
                    $property = Property::findOrFail($trip->property_id);
                    $name = Auth::user()->last_name;
                    Mail::to($user->email)->send(new TripMail($trip, $inviteLink, $property->registration_no, $property->type, $name));
                    Log::info("Invitation sent to {$user->email} for trip {$trip->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send invitation to {$user->email} for trip {$trip->id}: {$e->getMessage()}");
            }
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
}
