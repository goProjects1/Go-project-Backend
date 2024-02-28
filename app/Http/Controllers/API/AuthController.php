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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Intervention\Image\Facades\Image; // Add this line for Image class

class AuthController extends BaseController
{
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
        $success = $user;

        return $this->sendResponse($success, 'User created successfully.');
    }

    public function getRequesterIP()
    {
        return request()->ip();
    }

    public function attemptLogin(Request $request)
    {
        $email = $request->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid email format.'], 422);
        }
        $password = $request->get('password');
        $user = User::where('email', '=', $email)->first();

        // Implement rate limiting for user and IP address using Redis cache
        // Add rate limiting logic here if needed

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

            $success = "Success";
            return $this->sendResponse($success, 'OTP sent successfully.');
        } else {
            return response()->json(['message' => 'Record not found.'], 404);
        }
    }

    public function loginViaOtp(Request $request)
    {
        $user = User::where([['email', '=', $request->email], ['otp', '=', $request->otp]])->first();
        if ($user) {
            auth()->login($user, true);
            $user->otp = null;
            $user->save();
            $success['token'] = $user->createToken('MyAuthApp')->plainTextToken;
            return $this->sendResponse($success, "Success");
        } else {
            return response(["status" => 401, 'message' => 'Invalid']);
        }
    }

    public function updateProfile(Request $request)
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

    public function geocodeAddress(Request $request)
    {
        // Add geocoding logic here

        return response()->json(['message' => 'Geocoding logic not implemented yet.'], 501);
    }

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

    public function getProfile()
    {
        $id = Auth::user();
        $getProfileFirst = User::where('id', $id->id)->get();
        return response()->json($getProfileFirst);
    }
}
