<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class ForgetpasswordController extends BaseController
{
    //
    public function forgot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error validation', $validator->errors());
        }
        $email = $request -> input('email');
        if(User::where('email', $email)->doesntExist())
        {
            return response([
                'message' => 'user doesn\'t exists'
            ]);
        }
        $token = Str::random(10);
        try   {
            DB::table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
            ]);

            $data = [
                'token' => $token,
                'email' => $email
            ];
            //send email
            Mail::send('Email.forgot', $data , function ($message) use ($email) {
                $message->to($email);
                $message->subject('Go-project: Reset Password');
            });


        }catch (\Exception $exception){
            return response([
                'message' => $exception -> getMessage()
            ], 400);
        }
    }

    public function Reset(Request $request){

        $token = $request->input('token');

        if(!$passwordReset = DB::table('password_resets')->where('token', $token)->first())
        {
            return response ([
                'message' => 'Invalid token !'
            ], 403);
        }


        /** @var User $user  */


        $user = User::where('email', $passwordReset->email)->first();

        if(!$user)
        {
            return response([
                'message' => 'User doesn\'t exist'
            ], 403);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return $this->sendResponse($user, 'Success.');
    }
}
