<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Property;
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
            'destLatitude' => 'required',
            'destLongitude' => 'required',
        ]);

        $tripScheduleData = $request->all();
        $tripScheduleData['schedule_status'] = "active";
        $tripScheduleData['user_id'] = Auth::id();
        $ownProperty = $request->ownProperty;
        $available_seat = $request->available_seat;
        $planTime = $request->input('plan_time');
        $days = (array)$request->input('day');
        $toTimes = (array)$request->input('to_time');
        $allowUserMeetingPoint = $request->allowUserMeetingPoint;
     //   echo $allowUserMeetingPoint;
        if ($allowUserMeetingPoint) {
            $tripScheduleData['allowUserMeetingPoint'] = true;
        } else {
            $tripScheduleData['allowUserMeetingPoint'] = false;
        }

        if ($ownProperty) {
            $own = Property::where('id', $ownProperty)
                ->where('user_id', Auth::id())
                ->first();
            $tripScheduleData['ownProperty'] = $own;
            $tripScheduleData['available_seat'] = $available_seat;
        }

        if ($planTime === 'dynamic') {
            return $this->handleDynamicPlanTime($tripScheduleData, $days, $toTimes);
        } else {
            return $this->handleStaticPlanTime($tripScheduleData, $days[0], $toTimes[0]);
        }
    }

    private function handleDynamicPlanTime(array $tripScheduleData, array $days, array $toTimes): \Illuminate\Http\JsonResponse
    {
        $createdTripSchedules = [];

        foreach ($days as $index => $day) {
            $dynamicData = $this->prepareDynamicData($tripScheduleData, $day, $toTimes[$index]);

            $createdTripSchedule = $this->tripScheduleService->createTripSchedule($dynamicData);
            $createdTripSchedules[] = array_merge($createdTripSchedule->toArray(), ['day' => $day]);
        }

        return $this->sendResponse($createdTripSchedules, 'Trips created successfully');
    }

    private function handleStaticPlanTime(array $tripScheduleData, $day, $toTime): \Illuminate\Http\JsonResponse
    {
        $tripScheduleData['day'] = $day;
        $tripScheduleData['to_time'] = $toTime;

        $createdTripSchedule = $this->tripScheduleService->createTripSchedule($tripScheduleData);
        return $this->sendResponse(array_merge($createdTripSchedule->toArray(), ['day' => $day]), 'Trip scheduled successfully');
    }

    private function prepareDynamicData(array $tripScheduleData, $day, $toTime): array
    {
        $dynamicData = $tripScheduleData;
        $dynamicData['plan_time'] = 'dynamic';
        $dynamicData['day'] = $day;
        $dynamicData['to_time'] = $toTime;

        return $dynamicData;
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

        $update  =  $tripSchedule->update($validatedData);
        return $this->sendResponse($update, 'Trips updated successfully');

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


    public function acceptScheduleTrip(Request $request, $scheduleTripId): \Illuminate\Http\JsonResponse
    {
        $tripScheduleData = $this->tripScheduleService->acceptScheduleTrip($request, $scheduleTripId);

        return $this->sendResponse($tripScheduleData, 'Schedule trip request accepted');
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



    public function updateScheduleStatus(Request $request, $scheduleId)
    {

        $newStatus = $request->new_status;
        $update  =  $this->tripScheduleService->updateScheduleStatus($scheduleId, $newStatus);
        return $this->sendResponse($update, 'Trips schedule updated successfully');
    }

}
