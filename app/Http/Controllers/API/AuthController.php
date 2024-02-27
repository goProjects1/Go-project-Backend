<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;



class AuthController extends BaseController
{
    //


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'phone_number' => 'string|unique:users|required',
            'password' => 'required|confirmed|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error validation', $validator->errors());
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        //$success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;
        $success = $user;

        return $this->sendResponse($success, 'User created successfully.');
    }


    //login function

    public function getRequesterIP()
    {
        return request()->ip();
    }

    public function AttemptLogin(Request $request)
    {
        //try {
        $email = $request->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid email format.'], 422);
        }
        $password = $request->get('password');
        $user = User::where('email', '=', $email)->first();

        // Implement rate limiting for user and IP address using Redis cache
//            $userRateLimitKey = 'rate_limit:user:' . $user->id . ':' . $email;
//            $ipRateLimitKey = 'rate_limit:ip:' . $this->getRequesterIP();
//            $rateLimitDuration = 300; // 5 minutes
        $rateLimitMaxAttempts = 3;

//            $currentUserAttempts = (int) Redis::get($userRateLimitKey) ?? 0;
//            $currentIPAttempts = (int) Redis::get($ipRateLimitKey) ?? 0;

//    if ($currentUserAttempts >= $rateLimitMaxAttempts || $currentIPAttempts >= $rateLimitMaxAttempts) {
        //      return response(['message' => 'Too many login attempts. Try again after 5 minutes.'], 429);
        // }

        if (Hash::check($password, $user->password)) {
            $otp = random_int(0, 999999);
            $otp = str_pad($otp, 6, 0, STR_PAD_LEFT);
            Log::info("otp = " . $otp);

            // Update user's OTP in the database
            $user->otp = $otp;
            $user->save();

            // Send email with OTP
            $data = [
                'otp' => $otp,
                'email' => $email
            ];
            $subject = 'GOPROJECT: ONE TIME PASSWORD';
            Mail::send('Email.otp', $data, function ($message) use ($request, $subject) {
                $message->to($request->email)->subject($subject);
            });

            // Ensure to update the Redis keys when the login is successful
//                Redis::incr($userRateLimitKey);
//                Redis::incr($ipRateLimitKey);
//                if (!Redis::ttl($userRateLimitKey)) {
//                    Redis::expire($userRateLimitKey, $rateLimitDuration);
//                }
//                if (!Redis::ttl($ipRateLimitKey)) {
//                    Redis::expire($ipRateLimitKey, $rateLimitDuration);
//                }

            $success = "cool";
            return $this->sendResponse($success, 'User created successfully.');
            //     return response(["status" => 200, "message" => "OTP sent successfully"]);
        } else {
            return response()->json(['message' => 'Record not found.'], 404);
        }
//        } catch (QueryException $e) {
//            // Handle database query exceptions
//            return response(['message' => 'Database Error'], 500);
//        } catch (\Exception $e) {
//            // Handle other exceptions
//            return response(['message' => 'Internal Server Error'], 500);
//        }

    }

    public function loginViaOtp(Request $request)
    {
        $user = User::where([['email', '=', $request->email], ['otp', '=', $request->otp]])->first();
        if ($user) {
            auth()->login($user, true);
            $user->otp = null;
            $user->save();
            // $success['token'] = auth()->user()->createToken('authToken')->accessToken;

            // Reset the rate limit counters after successful login
//            $userRateLimitKey = 'rate_limit:user:' . $user->id . ':' . $request->email;
//            $ipRateLimitKey = 'rate_limit:ip:' . $this->getRequesterIP();
//            Redis::del($userRateLimitKey);
//            Redis::del($ipRateLimitKey);

            // Get and display the user data from Redis cache
            // $userDataKey = 'user:' . $user->id;
            // $userData = Redis::get($userDataKey);

//        if ($userData) {
            // Assuming user data was stored as JSON, decode it to an array for display
            //          $userArray = json_decode($userData, true);

            // Display the user data as needed
            // For example:
            //        echo "User ID: " . $userArray['id'] . "<br>";
            //      echo "Name: " . $userArray['name'] . "<br>";
            // and so on...
            // }
            $success['token'] = $user->createToken('MyAuthApp')->plainTextToken;
            return $this->sendResponse($success, "Success");
        } else {
            return response(["status" => 401, 'message' => 'Invalid']);
        }
    }

   public function profileImage(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'first_name' => 'required|string',
                'country' => 'required|string',
                'postcode' => 'required|string',
                'street' => 'required|string',
                'house_number' => 'required|string',
                'city' => 'required|string',
                'identity_card_no' => 'required|string',
                'nationality' => 'required|string',
                'dob' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'marital_status' => 'required|in:single,married,divorced,widowed',
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif',
                'identity_card' => 'required|file|mimes:pdf,jpeg,png,jpg,gif',
		'address' => 'required|string',
            ]);

          $user = Auth::user();

            // Update additional user data
            $user->update([
                'first_name' => $validatedData['first_name'],
                'country' => $validatedData['country'],
                'postcode' => $validatedData['postcode'],
                'street' => $validatedData['street'],
                'house_number' => $validatedData['house_number'],
                'city' => $validatedData['city'],
                'identity_card_no' => $validatedData['identity_card_no'],
                'nationality' => $validatedData['nationality'],
                'dob' => $validatedData['dob'],
                'gender' => $validatedData['gender'],
                'marital_status' => $validatedData['marital_status'],
		'address' => $validatedData['address'],
            ]);

            // Handle identity_card file
            if ($request->hasFile('identity_card') && $request->file('identity_card')->isValid()) {
                $identityCard = $request->file('identity_card');

                // Define the storage path
                $identityCardPath = 'storage/identity_cards/';

                // Move the uploaded file to the storage path with a unique name
                $identityCardPath = $identityCardPath . $identityCard->hashName();
                $identityCard->move(public_path($identityCardPath), $identityCard->hashName());

                // Update the user's identity_card attribute
                $user->identity_card = $identityCardPath;
            }

            // Handle profile image file
            if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                $profileImage = $request->file('profile_image');

                // Define the storage path
                $profileImagePath = 'storage/profiles/';

                // Move the uploaded image to the storage path with a unique name
                $profileImagePath = $profileImagePath . $profileImage->hashName();
                $profileImage->move(public_path($profileImagePath), $profileImage->hashName());

                // Update the user's profile_image attribute
                $user->profile_image = $profileImagePath;
            }

            // Set isVerify to 1 when all input fields are supplied
            $user->isVerify = 1;

            // Save the user model
            $user->save();

            // Return a JSON response with the profile image and identity card URLs and updated user details
            return response()->json([
                'profile_image' => asset($user->profile_image),
                'identity_card' => asset($user->identity_card),
                'user' => $user,
                'message' => 'User is verified',
            ], 200);
        } catch (ValidationException $validationException) {
            // Log validation errors
            Log::error('Validation error during profile image and user data update', [
                'validation_errors' => $validationException->errors(),
            ]);

            // Return a JSON response with validation errors
            return response()->json(['error' => 'Validation failed', 'errors' => $validationException->errors()], 422);
        } catch (\Exception $e) {
            // Log any unexpected exceptions
            Log::error('Profile image, identity card, and user data update failed unexpectedly', ['exception' => $e]);

            // Return a JSON response with a generic error message
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

  public function geocodeAddress(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Ensure the user is authenticated before proceeding
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Extract postcode from the request
        $postcode = $request->input('postcode');

        // Make a request to the OpenStreetMap Nominatim API
        $response = Http::get('https://nominatim.openstreetmap.org/search', [
            'q' => $postcode,
            'format' => 'json',
            'addressdetails' => 1,
        ]);

        // Check if the API request was successful
        if ($response->successful()) {
            // Retrieve data from the API response
            $data = $response->json();

            // Check if data is not empty
            if (!empty($data)) {
                // Extract information from the first result
                $firstResult = $data[0];

                // Extract coordinates
                $coordinates = [
                    'lat' => $firstResult['lat'],
                    'lng' => $firstResult['lon'],
                ];

                // Update the user's table with the obtained coordinates
                $user->update([
                    'latitude' => $coordinates['lat'],
                    'longitude' => $coordinates['lng'],
                ]);

                // Additional address details, including postal code
                $addressDetails = [
                    'city' => $firstResult['address']['city'] ?? $firstResult['address']['town'] ?? null,
                    'postcode' => $firstResult['address']['postcode'] ?? null,
                    'country' => $firstResult['address']['country'] ?? null,
                ];

                return array_merge($coordinates, $addressDetails);
            } else {
                return response()->json(['error' => 'No data found for the given postcode'], 404);
            }
        } else {
            return response()->json(['error' => 'Failed to retrieve data from the geocoding API'], $response->status());
        }
    }


    //logout function
public function logout()
{
    if (Auth::check()) {
        Auth::user()->tokens->each(function ($token, $key) {
            $token->delete();
        });

        return response()->json(["status" => "success", "error" => false, "message" => "Success! You are logged out."], 200);
    }

    return response()->json(["status" => "failed", "error" => true, "message" => "Failed! You are already logged out."], 403);
}


    public function logouts()
    {

        if(Auth::check()) {
            Auth::user()->token()->revoke();
            return response()->json(["status" => "success", "error" => false, "message" => "Success! You are logged out."], 200);
        }
        return response()->json(["status" => "failed", "error" => true, "message" => "Failed! You are already logged out."], 403);
    }

	 public function getProfile()
    {
        $id = Auth::user();
        $getProfileFirstt = user::where('id', $id->id)->get();
        return response()->json($getProfileFirstt);

    }




}
