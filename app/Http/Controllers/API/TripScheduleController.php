<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\TripSchedule;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;

class TripScheduleController extends BaseController
{
    //

    protected $tripScheduleService;

    public function __construct(TripSchedule $tripScheduleService)
    {
        $this->tripScheduleService = $tripScheduleService;
    }
    public function scheduleTrip(Request $request): \Illuminate\Http\Response
    {
        $request->validate([
            'type' => 'required',
            'pickUp' => 'required',
            'destination' => 'required',
            'variable_distance' => 'required',
            'frequency' => 'required',
            'to_time' => 'required',
            'from_time' => 'required',
            'description' => 'required',
        ]);

        $tripScheduleData = $request->all();
        $tripScheduleData['user_id'] = Auth::user()->getAuthIdentifier();

        $tripSchedule = new TripSchedule($tripScheduleData);
        $createdTripSchedule = $this->tripScheduleService->createTripSchedule($tripSchedule);

        return $this->sendResponse($createdTripSchedule, 'Trip created successfully');
    }


    public function updateTrip(Request $request, $id): \Illuminate\Http\Response
    {
        $validatedData = $request->validate([
            'type' => 'required',
            'pickUp' => 'required',
            'destination' => 'required',
            'day' => 'required',
            'variable_distance' => 'required',
            'frequency' => 'required',
            'to_time' => 'required',
            'from_time' => 'required',
            'description' => 'required',
            'user_id' => Auth::user()->getAuthIdentifier(),
        ]);

        $tripSchedule = TripSchedule::findOrFail($id);
        $tripSchedule['user_id'] = Auth::user()->getAuthIdentifier();
        $tripSchedule->update($validatedData);
        return $this->sendResponse($tripSchedule, 'Trip updated successfully');
    }

    public function deleteTrip($id): \Illuminate\Http\Response
    {
        $tripSchedule = TripSchedule::findOrFail($id);
        $tripSchedule->delete();

        return $this->sendResponse(null, 'Trip deleted successfully');
    }

    public function getTrip(): \Illuminate\Http\Response
    {
        $tripSchedule = TripSchedule::where('user_id', Auth::user()->getAuthIdentifier());

        return $this->sendResponse($tripSchedule, 'Trip details retrieved successfully');
    }

    public function getTripById($id): \Illuminate\Http\Response
    {
        $tripSchedule = TripSchedule::findOrFail($id);

        return $this->sendResponse($tripSchedule, 'Trip details retrieved successfully');
    }

}
