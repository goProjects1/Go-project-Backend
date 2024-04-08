<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Decline;
use App\Models\Property;
use App\Models\ReferralSetting;
use App\Services\ReferralService;
use App\Services\TripService;
use http\Client;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Models\Trip;
use Illuminate\Support\Facades\Auth;
use App\Services\OpenStreetMapService;



class TripController extends BaseController
{
    protected $tripService;

    public function __construct(TripService $tripService, ReferralService $referral)
    {
        $this->tripService = $tripService;
        $this->referral = $referral;
    }
    public function createTrip(Request $request)
    {
        // Validate the request data as needed
        $request->validate([
            'type' => 'required',
            'pickUp' => 'required',
            'destination' => 'required',
            'variable_distance' => 'required',
            'meeting_point' => 'required',
            'fee_amount' => 'required',
            'fee_option' => 'required',
            'charges' => 'required',
            'available_seat' => 'required',
            'description' => 'required',
            'property_id' => 'required'
        ]);

        // Check if user is currently on a trip
        $user_id = Auth::user()->getAuthIdentifier();
        $isOnTrip = Trip::where('sender_id', $user_id)
            ->where('trip_status', 'ongoing')
            ->exists();

        if ($isOnTrip) {
            return response()->json(['message' => 'You cannot initiate another trip while you are currently on one.'], 400);
        }

        // Fetch property details based on the provided property_id
        $property = Property::where('id', $request->property_id)
            ->where('user_id', Auth::user()->getAuthIdentifier())
            ->first();
        if (!$property) {
            return response()->json(['error' => 'Property does not belong to this user'], 403);
        }

        // Create new trip
        $trip = new Trip($request->all());
        $trip->sender_id = Auth::user()->getAuthIdentifier();
        $this->tripService->notifyUsersAndSaveTrip($trip);
        $modelType = "Create-Trip";
        $referralSet = ReferralSetting::where('status', 'active')
            ->latest('updated_at')
            ->first();
        if ($referralSet) {
            $this->referral->checkSettingEnquiry($modelType);
        }

        // response data
        $responseData = [
            'trip' => $trip,
            'property_name' => $property->type,
            'property_plate_number' => $property->registration_no
        ];
        return $this->sendResponse($responseData, 'Trip created successfully');
    }

    public function acceptTrip(Request $request): \Illuminate\Http\JsonResponse
    {
        $tripId = $request->input('trip_id');
        $trip = Trip::findOrFail($tripId);

        if ($this->tripService->acceptTrip($trip)) {
            return $this->sendResponse($trip, 'Trip accepted successfully');
        }

        return response()->json(['message' => 'User is not within the specified distance'], 400);
    }
    public function declineTrip(Request $request): \Illuminate\Http\JsonResponse
    {
        $inviteLink = $request->input('inviteLink');

        if (strpos($inviteLink, 'action=decline') !== false) {
            // Extract the token from the inviteLink
            $query = parse_url($inviteLink, PHP_URL_QUERY);
            parse_str($query, $params);
            $tripId = $params['tripId'];

            $invitation = Trip::where('id', $tripId)->first();

            if ($invitation) {
                // Update the status column to decline
                $invitation->trip_status = 'decline';
                $invitation->save();

                // Create a Decline record if needed
                $trip = Decline::create([
                    'reason' => $request->input('reason'),
                    'trip_id' => $invitation->id,
                    'user_id' => Auth::user()->getAuthIdentifier(),
                ]);
                return $this->sendResponse($trip, 'Trip declined successfully');

            } else {
                return response()->json(['error' => 'Invalid Trip Id'], 400);
            }
        } else {
            return response()->json(['error' => 'Invalid action'], 400);
        }
    }

    public function getUsersTrip(Request $request)
    {
        // Get the authenticated user's ID
        $userId = auth()->id();

        // Call the service method to retrieve all trips per user
        $allTrips = $this->tripService->getAllTripsPerUser($userId);

        return $this->sendResponse($allTrips, 'Trips retrieved successfully');
    }

    public function getUsersTripAsPassenger(): \Illuminate\Http\JsonResponse
    {
        $allTrips = $this->tripService->getAllTripsAsPassenger();
        return $this->sendResponse($allTrips, 'Trips retrieved successfully');
    }

    public function getTripDetailsById(Request $request, $tripId)
    {
        // Call the service method to retrieve trip details by trip_id
        $trip = $this->tripService->getTripDetails($tripId);

        if (!$trip) {
            return response()->json(['error' => 'Trip not found.'], 404);
        }

        return response()->json(['trip' => $trip], 200);
    }

}
