<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    //

    public function getTripReport(): \Illuminate\Http\JsonResponse
    {
        $trips = Trip::selectRaw('COUNT(*) as total_trip_count, SUM(fee_amount) as total, MONTH(created_at) as month, YEAR(created_at) as year')
            ->groupBy('month', 'year')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->where(function ($query) {
                $userId = Auth::user()->id;
                $query->where('sender_id', $userId)
                    ->orWhere('guest_id', $userId);
            })
            ->whereIn('trip_status', ['accepted', 'declined'])
            ->get();

        $acceptedTrips = $trips->where('trip_status', 'accepted')->sum('total_trip_count');
        $declinedTrips = $trips->where('trip_status', 'declined')->sum('total_trip_count');

        $result = [];
        foreach ($trips as $trip) {
            $result[] = [
                'total_trip_count' => $trip->total,
                'accepted_trip_count' => $acceptedTrips,
                'declined_trip_count' => $declinedTrips,
                'total' => $trip->total,
                'month' => date('F', mktime(0, 0, 0, $trip->month, 1)),
                'year' => $trip->year,
            ];
        }
        return response()->json(['data' => $result]);
    }
}
