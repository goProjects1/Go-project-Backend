<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

    public function updateProfilee(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'string|min:2|max:45',
            'last_name' => 'string|min:2|max:45',
            'country' => 'string',
            'postcode' => 'string|min:7|max:7',
            'street' => 'string',
            'house_number' => 'string',
            'city' => 'string',
            'identity_card' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'nationality' => 'string',
            'dob' => 'string',
            'gender' => 'string',
            'marital_status' => 'string',
            'profile_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Update user profile with validated data
        $user->fill($validator->validated());

        // Handle identity card update if provided
        if ($request->hasFile('identity_card')) {
            $image = $request->file('identity_card');
            $imageName = 'identity_card_' . time() . '.' . $image->getClientOriginalExtension();

            // Resize the image if needed
            $resizedImage = Image::make($image)->fit(600, 600)->encode();

            // Store the image in the storage/app/public directory
            \Storage::disk('public')->put($imageName, $resizedImage);

            // Update the user's identity_card field with the image path
            $user->identity_card = $imageName;
        }

        // Handle profile image update if provided
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = 'profile_' . time() . '.' . $image->getClientOriginalExtension();

            // Resize the image if needed
            $resizedImage = Image::make($image)->fit(300, 300)->encode();

            // Store the image in the storage/app/public directory
            \Storage::disk('public')->put($imageName, $resizedImage);

            // Update the user's profile_image field with the image path
            $user->profile_image = $imageName;
        }

        $user->save();

        return $this->sendResponse($user, 'Profile updated successfully.');
    }


    public function updateUserProfile(Request $request)
    {
        $user = Auth::user();

        // Update the user details
        $user->update([
            'first_name' => $request->input('first_name'),
            'country' => $request->input('country'),
            'postcode' => $request->input('postcode'),
            'street' => $request->input('street'),
            'house_number' => $request->input('house_number'),
            'city' => $request->input('city'),
            'identity_card_no' => $request->input('identity_card_no'),
            'nationality' => $request->input('nationality'),
            'dob' => $request->input('dob'),
            'gender' => $request->input('gender'),
            'marital_status' => $request->input('marital_status'),
        ]);

        // Handle profile image and identity card updates
        $profileImagePath = $this->handleFileUpload($request, 'profile_image', $user, 'profiles');
        $identityCardPath = $this->handleFileUpload($request, 'identity_card', $user, 'profiles');

        // Return the response with user details and file paths
        return $this->sendResponse([
            'user' => $user,
            'profile_image' => $profileImagePath,
            'identity_card' => $identityCardPath
        ], 'Profile updated successfully.');
    }

    private function handleFileUpload(Request $request, $fieldName, $user, $storageFolder)
    {
        if ($request->hasFile($fieldName) && $request->file($fieldName)->isValid()) {
            // Get the uploaded file from the request
            $file = $request->file($fieldName);

            // Generate a unique filename for the file
            $filename = uniqid() . '_' . $file->getClientOriginalName();

            // Save the file to the specified storage folder
            $file->storeAs($storageFolder, $filename, 'public');

            // Update the user model with the file path
            $user->{$fieldName} = $filename;

            // Save the user model to persist the changes
            $user->save();

            // Return the file path
            return url('storage/' . $storageFolder . '/' . $filename);
        }

        return null;
    }


    public function profileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif',
        ]);

        $user = Auth::user();
        $path = null;

        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $image = $request->file('profile_image');
            $path = 'storage/profiles/' . $image->hashName();
            $image->move(public_path('storage/profiles'), $image->hashName());
            $user->profile_image = $path;
        }

        $user->save();
        return response()->json(['profile_image' => asset($user->profile_image), 'user' => $user], 200);
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






}
