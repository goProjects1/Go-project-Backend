<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
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
        ]);
        // Create trip and notify users within the variable distance
        $trip = new Trip($request->all());
        $trip->sender_id = Auth::user()->getAuthIdentifier();
        $trip = $this->tripService->createTripAndNotifyUsers($trip);
        return $this->sendResponse($trip, 'Trip created successfully');

    }

    public function acceptTrip(Request $request, $tripId, $userId)
    {
        // Implement logic to handle user acceptance of the trip
        $trip = Trip::findOrFail($tripId);

        if ($this->tripService->acceptTrip($trip, $userId)) {
            return response()->json(['message' => 'Trip accepted successfully']);
        }

        return response()->json(['message' => 'User is not within the specified distance'], 400);
    }
}
