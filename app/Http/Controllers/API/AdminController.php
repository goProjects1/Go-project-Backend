<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AdminController extends Controller
{
    //
    public function adminRegister(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the request for admin registration
        $this->validate($request, [
            'phone_number' => 'string|unique:users|required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'password_confirmation' => 'required|same:password',
        ]);

        // Create an admin user
        $user = new User([
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'usertype' => 'Admin',
            'password' => Hash::make($request->password)
        ]);
        $user->save();
        return response()->json(['message' => 'Admin user has been registered', 'data' => $user], 200);
    }


    public function getProfile(): \Illuminate\Http\JsonResponse
    {
        $id = Auth::user();
        $getProfileFirst = user::where('id', $id->id)->get();
        return response()->json($getProfileFirst);

    }

    public function getAllUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $users = User::paginate($request->query('per_page', 10));
        return response()->json($users);
    }

    public function getAllUsersByEmail(Request $request, $email): \Illuminate\Http\JsonResponse
    {
        $user = User::where('email', $email)->first();
        return response()->json($user);
    }
    public function getAllTrips(Request $request): \Illuminate\Http\JsonResponse
    {
        $trips = Trip::paginate($request->query('per_page', 10));
        return response()->json($trips);
    }
    public function getAllTripsPerId(Request $request, $tripId): \Illuminate\Http\JsonResponse
    {
        $trip = Trip::where('id', $tripId)->first();
        return response()->json($trip);
    }
    public function getAllCompletedTrips(Request $request): \Illuminate\Http\JsonResponse
    {
        $completedTrips = Trip::where('trip_status', 'completed')->paginate($request->query('per_page', 10));

        return response()->json($completedTrips);
    }

    public function getAllAcceptedTrips(Request $request): \Illuminate\Http\JsonResponse
    {
        $acceptedTrips = Trip::where('trip_status', 'accepted')->paginate($request->query('per_page', 10));

        return response()->json($acceptedTrips);
    }
    public function getPendingTrips(Request $request): \Illuminate\Http\JsonResponse
    {
        $pendingTrips = Trip::where('trip_status', 'pending')->paginate($request->query('per_page', 10));
        return response()->json($pendingTrips);
    }

    public function getAllFailedTrips(Request $request): \Illuminate\Http\JsonResponse
    {
        $failedTrips = Trip::where('trip_status', 'decline')->paginate($request->query('per_page', 10));

        return response()->json($failedTrips);
    }

    public function getAllFeedbacks(Request $request): \Illuminate\Http\JsonResponse
    {
        $feedbacks = Feedback::paginate($request->query('per_page', 10));
        return response()->json($feedbacks);
    }



}
