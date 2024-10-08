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
    // public function createTrip(Request $request): \Illuminate\Http\JsonResponse
    // {
    //     // Validate the request data as needed
    //     $request->validate([
    //         'type' => 'required',
    //         'pickUp' => 'required',
    //         'destination' => 'required',
    //         'variable_distance' => 'required',
    //         'meeting_point' => 'required',
    //         'fee_amount' => 'required',
    //         'fee_option' => 'required',
    //         'available_seat' => 'required',
    //         'description' => 'required',
    //         'property_id' => 'required',
    //         'latitude'  => 'required',
    //         'longitude' => 'required',
    //         'destLatitude' => 'required',
    //         'destLongitude' => 'required'
    //     ]);
    //     // Check if user is currently on a trip
    //     $user_id = Auth::user()->getAuthIdentifier();
    //     $isOnTrip = Trip::where('sender_id', $user_id)
    //         ->where('trip_status', 'ongoing')
    //         ->exists();

    //     if ($isOnTrip) {
    //         return response()->json(['message' => 'You cannot initiate another trip while you are currently on one.'], 400);
    //     }

    //     // Fetch property details based on the provided property_id
    //     $property = Property::where('id', $request->property_id)
    //         ->where('user_id', Auth::user()->getAuthIdentifier())
    //         ->first();
    //     if (!$property) {
    //         return response()->json(['error' => 'Property does not belong to this user'], 403);
    //     }

    //     // Create new trip
    //     $trip = new Trip($request->all());
    //     $trip->sender_id = Auth::user()->getAuthIdentifier();
    //     $trip->charges = $request->fee_amount * 0.005;
    //     $trip->journey_status = "waiting";
    //     $this->tripService->notifyUsersAndSaveTrip($trip);
    //     $modelType = "Create-Trip";
    //     $referralSet = ReferralSetting::where('status', 'active')
    //         ->latest('updated_at')
    //         ->first();
    //     if ($referralSet) {
    //         $this->referral->checkSettingEnquiry($modelType);
    //     }

    //     $responseData = [
    //         'trip' => $trip,
    //         'property_name' => $property->type,
    //         'property_plate_number' => $property->registration_no
    //     ];
    //     return $this->sendResponse($responseData, 'Trip created successfully');
    // }

    // public function acceptTrip(Request $request): \Illuminate\Http\JsonResponse
    // {
    //     $tripId = $request->input('trip_id');
    //     $trip = Trip::findOrFail($tripId);

    //     if ($this->tripService->acceptTrip($trip)) {
    //         return $this->sendResponse($trip, 'Trip accepted successfully');
    //     }

    //     return response()->json(['message' => 'User is not within the specified distance'], 400);
    // }
    // public function declineTrip(Request $request): \Illuminate\Http\JsonResponse
    // {
    //     $inviteLink = $request->input('inviteLink');

    //     if (strpos($inviteLink, 'action=decline') !== false) {
    //         // Extract the token from the inviteLink
    //         $query = parse_url($inviteLink, PHP_URL_QUERY);
    //         parse_str($query, $params);
    //         $tripId = $params['tripId'];

    //         $invitation = Trip::where('id', $tripId)->first();

    //         if ($invitation) {
    //             // Update the status column to decline
    //             $invitation->trip_status = 'decline';
    //             $invitation->save();

    //             // Create a Decline record if needed
    //             $trip = Decline::create([
    //                 'reason' => $request->input('reason'),
    //                 'trip_id' => $invitation->id,
    //                 'user_id' => Auth::user()->getAuthIdentifier(),
    //             ]);
    //             return $this->sendResponse($trip, 'Trip declined successfully');

    //         } else {
    //             return response()->json(['error' => 'Invalid Trip Id'], 400);
    //         }
    //     } else {
    //         return response()->json(['error' => 'Invalid action'], 400);
    //     }
    // }
    
    
    
    // public function createTrip(Request $request): \Illuminate\Http\JsonResponse
    // {
    //     $request->validate([
    //         'type' => 'required',
    //         'pickUp' => 'required',
    //         'destination' => 'required',
    //         'meeting_point' => 'required',
    //         'fee_amount' => 'required',
    //         'fee_option' => 'required',
    //         'available_seat' => 'required',
    //         'description' => 'required',
    //         'property_id' => 'required',
    //         'latitude' => 'required',
    //         'longitude' => 'required',
    //         'destLatitude' => 'required',
    //         'destLongitude' => 'required'
    //     ]);

    //     $user_id = Auth::user()->getAuthIdentifier();
    //     $isOnTrip = Trip::where('sender_id', $user_id)
    //         ->where('trip_status', 'ongoing')
    //         ->exists();

    //     if ($isOnTrip) {
    //         return response()->json(['message' => 'You cannot initiate another trip while you are currently on one.'], 400);
    //     }

    //     $property = Property::where('id', $request->property_id)
    //         ->where('user_id', $user_id)
    //         ->first();
    //     if (!$property) {
    //         return response()->json(['error' => 'Property does not belong to this user'], 403);
    //     }

    //     $trip = new Trip($request->all());
    //     $trip->sender_id = $user_id;
    //     $trip->charges = $request->fee_amount * 0.005;
    //     $trip->journey_status = "waiting";
    //     $trip->variable_distance = TripService::INITIAL_DISTANCE;

    //     $this->tripService->notifyUsersAndSaveTrip($trip);

    //     $modelType = "Create-Trip";
    //     $referralSet = ReferralSetting::where('status', 'active')
    //         ->latest('updated_at')
    //         ->first();
    //     if ($referralSet) {
    //         $this->referral->checkSettingEnquiry($modelType);
    //     }

    //     $responseData = [
    //         'trip' => $trip,
    //         'property_name' => $property->type,
    //         'property_plate_number' => $property->registration_no
    //     ];
    //     return $this->sendResponse($responseData, 'Trip created successfully');
    // }
    
    
    public function createTrip(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'type' => 'required',
            'pickUp' => 'required',
            'destination' => 'required',
            'meeting_point' => 'required',
            'fee_amount' => 'required',
            'fee_option' => 'required',
            'available_seat' => 'required',
            'description' => 'required',
        //    'property_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'destLatitude' => 'required',
            'destLongitude' => 'required'
        ]);

        $user_id = Auth::user()->getAuthIdentifier();
        $isOnTrip = Trip::where('sender_id', $user_id)
            ->where('trip_status', 'ongoing')
            ->exists();

        if ($isOnTrip) {
            return response()->json(['message' => 'You cannot initiate another trip while you are currently on one.'], 400);
        }

        $property = Property::where('id', $request->property_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$property) {
            return response()->json(['error' => 'Property does not belong to this user'], 403);
        }

        $totalMiles = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $request->destLatitude,
            $request->destLongitude
        );

        $trip = new Trip($request->all());
        $trip->sender_id = $user_id;
        $trip->charges = $request->fee_amount * 0.005;
        $trip->journey_status = "waiting";
       // $trip->variable_distance = $totalMiles;

        $this->tripService->notifyUsersAndSaveTrip($trip);

        $modelType = "Create-Trip";
        $referralSet = ReferralSetting::where('status', 'active')
            ->latest('updated_at')
            ->first();

        if ($referralSet) {
            $this->referral->checkSettingEnquiry($modelType);
        }

        $responseData = [
            'trip' => $trip,
            'property_name' => $property->type,
            'property_plate_number' => $property->registration_no,
            'total_miles' => $totalMiles
        ];

        return $this->sendResponse($responseData, 'Trip created successfully');
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles;
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
            $query = parse_url($inviteLink, PHP_URL_QUERY);
            parse_str($query, $params);
            $tripId = $params['tripId'];

            $invitation = Trip::where('id', $tripId)->first();

            if ($invitation) {
                $invitation->trip_status = 'decline';
                $invitation->save();

                $decline = Decline::create([
                    'reason' => $request->input('reason'),
                    'trip_id' => $invitation->id,
                    'user_id' => Auth::user()->getAuthIdentifier(),
                ]);
                return $this->sendResponse($decline, 'Trip declined successfully');
            } else {
                return response()->json(['error' => 'Invalid Trip Id'], 400);
            }
        } else {
            return response()->json(['error' => 'Invalid action'], 400);
        }
    }

    public function getAcceptedUsersCount($tripId): \Illuminate\Http\JsonResponse
    {
        $count = $this->tripService->getAcceptedUsersCount($tripId);
        return response()->json(['accepted_users_count' => $count]);
    }
    


    public function getUsersTrip(Request $request): \Illuminate\Http\JsonResponse
    {
        // Get the authenticated user's ID
        $userId = auth()->id();

        $allTrips = $this->tripService->getAllTripsPerUser($userId);

        return $this->sendResponse($allTrips, 'Trips retrieved successfully');
    }

    public function getUsersTripAsPassenger(): \Illuminate\Http\JsonResponse
    {
        $allTrips = $this->tripService->getAllTripsAsPassenger();

        return $this->sendResponse($allTrips, 'Trips retrieved successfully');
    }


    public function getTripDetailsById($tripId): \Illuminate\Http\JsonResponse
    {
        $trip = $this->tripService->getTripDetails($tripId);

        if (!$trip) {
            return response()->json(['error' => 'Trip not found.'], 404);
        }

        return response()->json(['trip' => $trip], 200);
    }

    public function updateTripStatus(Request $request, $tripId): \Illuminate\Http\JsonResponse
    {
        $newStatus = $request->new_status;
        $lat = $request->input('latitude');
        $long = $request->input('longitude');
        $update  =  $this->tripService->updateTripStatus($tripId, $newStatus, $lat, $long);
        return $this->sendResponse($update, 'Trips status updated successfully');
    }


    public function updateTripStatusForPassengers(Request $request, $tripId): \Illuminate\Http\JsonResponse
    {
        // Extract new status from the request
        $newStatus = $request->input('journey_status');
        $lat = $request->input('latitude');
        $long = $request->input('longitude');
        // Call the trip service to update trip status for passengers
        $update  = $this->tripService->updateTripStatusForPassanger($tripId, $newStatus,$lat, $long);
        return $this->sendResponse($update, 'Trips status updated successfully');
    }

    public function getTripsWithSchedules(): \Illuminate\Http\JsonResponse
    {
        $trips = $this->tripService->getTripsWithSchedules();
        return response()->json($trips);
    }

}
