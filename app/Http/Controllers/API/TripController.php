<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Property;
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

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
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

        // Fetch property details based on the provided property_id
        $property = Property::where('id', $request->property_id)
            ->where('user_id', Auth::user()->getAuthIdentifier())
            ->first();
        if (!$property) {
            return response()->json(['error' => 'Property does not belong to this user'], 403);
        }

        $trip = new Trip($request->all());
        $trip->sender_id = Auth::user()->getAuthIdentifier();
        $this->tripService->createTripAndNotifyUsers($trip);

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
}
