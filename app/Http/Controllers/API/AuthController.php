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

    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'string|min:2|max:45',
                'last_name' => 'string|min:2|max:45',
                'country' => 'string',
                'postcode' => 'string|min:7|max:7',
                'street' => 'string',
                'house_number' => 'string',
                'city' => 'string',
                'identity_card_no' => 'string',
                'nationality' => 'string',
                'dob' => 'string',
                'gender' => 'string',
                'marital_status' => 'string',
            ]);

            if ($validator->fails()) {
                $error = $validator->errors()->first();
                return response()->json(['status' => 'false', 'message' => $error, 'data' => []], 422);
            } else {
                $user = User::find($request->user()->id);

                // Check and update first name, last name, and other fields
                $user->fill($request->except(['profile_image', 'identity_card']));

                $user->save();
                return response()->json(['status' => 'true', 'message' => "Profile updated successfully", 'data' => $user]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => $e->getMessage(), 'data' => []], 500);
        }
    }

    public function handleFileUpload(Request $request, $userId): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image_profile' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'identity_card' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return response()->json(['status' => 'false', 'message' => $error, 'data' => []], 422);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json(['status' => 'false', 'message' => 'User not found', 'data' => []], 404);
        }

        if (Auth::id() == $userId) {
            // Handle image_profile file upload
            if ($request->hasFile('image_profile')) {
                $imageProfilePath = $request->file('image_profile')->store('public');

                // Check if the new file is different from the existing one
                if ($user->image_profile !== $imageProfilePath) {
                    $user->image_profile = $imageProfilePath;
                }
            }

            // Handle identity_card file upload
            if ($request->hasFile('identity_card')) {
                $identityCardPath = $request->file('identity_card')->store('public');

                // Check if the new file is different from the existing one
                if ($user->identity_card !== $identityCardPath) {
                    $user->identity_card = $identityCardPath;
                }
            }

            $user->save();

            return response()->json([
                'status' => 'true',
                'message' => 'File uploads successful',
                'data' => [
                    'profile_image' => Storage::url($user->image_profile),
                    'identity_card' => Storage::url($user->identity_card),
                ]
            ]);
        }

        return response()->json(['status' => 'false', 'message' => 'Unauthorized', 'data' => []], 403);
    }

    //logout function

    public function logout(): \Illuminate\Http\JsonResponse
    {

        if(Auth::check()) {
            Auth::user()->token()->revoke();
            return response()->json(["status" => "success", "error" => false, "message" => "Success! You are logged out."], 200);
        }
        return response()->json(["status" => "failed", "error" => true, "message" => "Failed! You are already logged out."], 403);
    }





}
