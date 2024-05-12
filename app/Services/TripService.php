<?php

namespace App\Services;

use App\Mail\TripDecline;
use App\Mail\TripNotification;
use App\Models\Property;
use App\Models\Trip;
use App\Mail\TripMail;
use App\Models\User;
use http\Env\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class TripService
{
    public function notifyUsersAndSaveTrip(Trip $trip): void
    {
        // Log users within distance
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
        Log::info('Users within distance for trip ' . $trip->id . ': ' . $usersWithinDistance->toJson());

        $tripCreatorId = $trip->sender_id;
        foreach ($usersWithinDistance as $user) {
            if ($user->id !== $tripCreatorId) {
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
                        $name = $user->last_name; // Use $user->last_name instead of Auth::user()->last_name

                        // Send trip notification
                        Mail::to($user->email)->send(new TripMail($newTrip, $inviteLink, $property->registration_no, $property->model, $property->type, $name));
                        Log::info("Invitation sent to {$user->email} for trip {$newTrip->id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send invitation to {$user->email} for trip: {$e->getMessage()}");
                }
            } else {
                try {
                    $inviteLink = 'https://go-project-ashy.vercel.app/account/trip_invite?tripId=' . $trip->id;
                    if (isset($user->email) && is_string($user->email)) {
                        // Fetch property details for the trip
                        $property = Property::findOrFail($trip->property_id);
                        $name = $user->last_name;
                        $this->saveTripForUser($trip, $user->id);

                        // Send trip notification
                        Mail::to($user->email)->send(new TripMail($trip, $inviteLink, $property->registration_no, $property->model, $property->type, $name));
                        Log::info("Invitation sent to {$user->email} for trip {$trip->id}");

                        // Save the trip for the user
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send invitation to {$user->email} for trip {$trip->id}: {$e->getMessage()}");
                }
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


    public function getAllTripsPerUser($userId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $perPage = 10;
        $user_id = Auth::user()->getAuthIdentifier();

        return Trip::where('sender_id', $user_id)->paginate($perPage);

    }


    public function getAllTripsAsPassenger()
    {
        $perPage = 10;
        return Trip::where('guest_id', auth()->id())
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }
    public function getTripDetails($tripId)
    {
        // Fetch trip details with sender's email
        $trip = Trip::with(['sender:id,email'])->find($tripId);

        if ($trip) {
            // Fetch all guest details (IDs and emails) from the User table for the given tripId
            $guests = User::whereIn('id', function ($query) use ($tripId) {
                $query->select('guest_id')->from('trips')->where('id', $tripId);
            })->get(['id', 'email']);

            // Append guest details to the trip object
            $trip->guest_details = $guests;

            return $trip;
        }

        // If trip with given tripId is not found, return null or handle accordingly
        return null;
    }

    public function updateTripStatus($tripId, $newStatus): string
    {
        // Get the authenticated user
        $user = Auth::user();

        // Find the trip schedule by ID
        $schedule = Trip::find($tripId);

        // Check if the schedule exists
        if (!$schedule) {
            return "Trip not found.";
        }

        // Check if the authenticated user owns the trip schedule
        if ($user->id !== $schedule->user_id) {
            return "Unauthorized. You do not own this trip.";
        }

        // Update the schedule status
        $schedule->journey_status = $newStatus;
        $schedule->save();

        return "Trip status updated successfully.";
    }


}
