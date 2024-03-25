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
        // Log users within distance
        $usersWithinDistance = $this->getUsersWithinDistance($trip);
        Log::info('Users within distance for trip ' . $trip->id . ': ' . $usersWithinDistance->toJson());


        foreach ($usersWithinDistance as $user) {
            try {

                $newTrip = clone $trip;


                $newTrip->guest_id = $user->id;

                // Save the new trip for the user
                $newTrip->save();

                // Construct invitation link with the new trip ID
                $inviteLink = 'https://go-project-ashy.vercel.app/account/trip_invite?tripId=' . $newTrip->id;

                if (isset($user->email) && is_string($user->email)) {
                    // Fetch property details for the trip
                    $property = Property::findOrFail($trip->property_id);
                    $name = Auth::user()->last_name;

                    // Send trip notification
                    Mail::to($user->email)->send(new TripMail($newTrip, $inviteLink, $property->registration_no, $property->type, $name));
                    Log::info("Invitation sent to {$user->email} for trip {$newTrip->id}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send invitation to {$user->email} for trip: {$e->getMessage()}");
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

        // Query to get users within the specified distance range
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
