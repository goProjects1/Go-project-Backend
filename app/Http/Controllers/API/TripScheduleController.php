<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TripSchedule;
use App\Services\TripSchedules;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;

class TripScheduleController extends BaseController
{
    //

    protected $tripScheduleService;

    public function __construct(TripSchedules $tripScheduleService)
    {
        $this->tripScheduleService = $tripScheduleService;
    }
    public function scheduleTrip(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'usertype' => 'required',
            'pickUp' => 'required',
            'destination' => 'required',
            'variable_distance' => 'required',
            'description' => 'required',
        ]);

        $tripScheduleData = $request->all();
        $tripScheduleData['user_id'] = Auth::user()->getAuthIdentifier();

        if ($request->input('plan_time') == 'dynamic') {
            // If plan_time is dynamic, create entries for each day with respective to_time
            $days = $request->input('day');
            $toTimes = $request->input('to_time');

            $createdTripSchedules = [];

            foreach ($days as $index => $day) {
                $dynamicData = [
                    'usertype' => $tripScheduleData['usertype'],
                    'pickUp' => $tripScheduleData['pickUp'],
                    'destination' => $tripScheduleData['destination'],
                    'variable_distance' => $tripScheduleData['variable_distance'],
                    'description' => $tripScheduleData['description'],
                    'user_id' => $tripScheduleData['user_id'],
                    'plan_time' => $request->input('plan_time'),
                    'day' => $day,
                    'to_time' => $toTimes[$index],
                    'frequency' => $tripScheduleData['frequency'],
                    'amount' => $tripScheduleData['amount'],
                    'pay_option' => $tripScheduleData['pay_option'],
                ];

                $createdTripSchedule = $this->tripScheduleService->createTripSchedule($dynamicData);
                $createdTripSchedules[] = array_merge($createdTripSchedule->toArray(), ['day' => $day]);
            }

            return $this->sendResponse($createdTripSchedules, 'Trips created successfully');
        } else {

            $createdTripSchedule = $this->tripScheduleService->createTripSchedule($tripScheduleData);
            return $this->sendResponse(array_merge($createdTripSchedule->toArray(), ['day' => $request->input('day')]), 'Trip scheduled successfully');

        }
    }




    public function updateTrip(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validate([
            'type' => 'required',
            'pickUp' => 'required',
            'destination' => 'required',
            'day' => 'required',
            'plan_time' => 'required',
            'variable_distance' => 'required',
            'frequency' => 'required',
            'to_time' => 'required',
            'from_time' => 'required',
            'description' => 'required',

        ]);

        $tripSchedule = TripSchedule::findOrFail($id);
        $tripSchedule['user_id'] = Auth::user()->getAuthIdentifier();
        $tripSchedule->update($validatedData);
        return $this->sendResponse($tripSchedule, 'Trip updated successfully');
    }

    public function deleteTrip($id): \Illuminate\Http\JsonResponse
    {
        $tripSchedule = TripSchedule::findOrFail($id);
        $tripSchedule->delete();

        return $this->sendResponse(null, 'Trip deleted successfully');
    }

    public function getTrip(): \Illuminate\Http\JsonResponse
    {
        $tripSchedule = TripSchedule::where('user_id', Auth::user()->getAuthIdentifier());

        return $this->sendResponse($tripSchedule, 'Trip details retrieved successfully');
    }

    public function getTripById($id): \Illuminate\Http\JsonResponse
    {
        $tripSchedule = TripSchedule::findOrFail($id);

        return $this->sendResponse($tripSchedule, 'Trip details retrieved successfully');
    }

}
