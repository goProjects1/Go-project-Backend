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
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $tripScheduleData = $request->all();
        $tripScheduleData['schedule_status']  =  "active";
        $tripScheduleData['user_id'] = Auth::user()->getAuthIdentifier();

        $planTime = $request->input('plan_time');
        $days = (array) $request->input('day');
        $toTimes = (array) $request->input('to_time');

        if ($planTime == 'dynamic') {

            $createdTripSchedules = [];

            foreach ($days as $index => $day) {
                $dynamicData = [
                    'usertype' => $tripScheduleData['usertype'],
                    'pickUp' => $tripScheduleData['pickUp'],
                    'destination' => $tripScheduleData['destination'],
                    'variable_distance' => $tripScheduleData['variable_distance'],
                    'description' => $tripScheduleData['description'],
                    'user_id' => $tripScheduleData['user_id'],
                    'plan_time' => $planTime,
                    'day' => $day,
                    'latitude' => $tripScheduleData['latitude'],
                    'longitude' => $tripScheduleData['longitude'],
                    'schedule_status'  =>  "active",
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

            $tripScheduleData['day'] = $days[0];
            $tripScheduleData['to_time'] = $toTimes[0];

            $createdTripSchedule = $this->tripScheduleService->createTripSchedule($tripScheduleData);
            return $this->sendResponse(array_merge($createdTripSchedule->toArray(), ['day' => $days[0]]), 'Trip scheduled successfully');
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
        $tripSchedule = TripSchedule::where('user_id', Auth::user()->id)->paginate(10);

        return $this->sendResponse($tripSchedule, 'Trip details retrieved successfully');
    }

    public function getTripById($id): \Illuminate\Http\JsonResponse
    {
        $tripSchedule = TripSchedule::findOrFail($id);

        return $this->sendResponse($tripSchedule, 'Trip details retrieved successfully');
    }


    public function getAllScheduledJourney(Request $request): \Illuminate\Http\JsonResponse
    {
        // Retrieve latitude and longitude from request
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        // Validate request data
        if (is_null($latitude) || is_null($longitude)) {
            return response()->json(['error' => 'Latitude and longitude are required.'], 400);
        }

        try {
            $schedules = $this->tripScheduleService->getAllScheduledJourney($latitude, $longitude);

            if ($schedules->isEmpty()) {
                return response()->json(['message' => 'No active cars are scheduled within this location.'], 404);
            }

            return response()->json($schedules, 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }



    public function updateScheduleStatus(Request $request, $scheduleId): string
    {

        $newStatus = $request->new_status;

        return $this->tripScheduleService->updateScheduleStatus($scheduleId, $newStatus);
    }

}
