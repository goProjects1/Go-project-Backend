<?php

use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\TripController;
use App\Http\Controllers\API\TripScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\ForgetpasswordController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/attemptLogin', [AuthController::class, 'AttemptLogin']);
Route::post('/loginViaOtp', [AuthController::class, 'loginViaOtp']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [ForgetpasswordController::class, 'forgot']);
Route::post('/reset', [ForgetpasswordController::class, 'reset']);

// Auth guided routes
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/ops', [AuthController::class, 'geocodeAddress']);
    Route::put('/handleFileUpload/{userId}', [AuthController::class, 'handleFileUpload']);
    Route::resource('property', PropertyController::class);
    Route::get('/getUserProperty',[PropertyController::class, 'getUserProperty']);
    Route::resource('student', StudentController::class);
    Route::get('/getUserSchool',[StudentController::class, 'getUserSchool']);
    Route::resource('job', JobController::class);
    Route::get('/getUserJob',[JobController::class, 'getUserJob']);
    Route::get('/ops', [AuthController::class, 'geocodeAddress']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/updateProfile', [AuthController::class, 'profileImage']);
    Route::get('getProfile',  [AuthController::class, 'getProfile']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/create-trip', [TripController::class, 'createTrip']);
    Route::post('/accept-trip', [TripController::class, 'acceptTrip']);
    Route::post('/create-scheduleTrip', [TripScheduleController::class, 'scheduleTrip']);
    Route::get('/get-scheduleTrip-by-id/{id}', [TripScheduleController::class, 'getTripById']);
    Route::get('/get-all-scheduleTrip-perUser', [TripScheduleController::class, 'getTrip']);
    Route::post('/update-scheduleTrip/{id}', [TripScheduleController::class, 'updateTrip']);
    Route::delete('/delete-scheduleTrip/{id}', [TripScheduleController::class, 'deleteTrip']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::post('/feedback/{id}/reply', [FeedbackController::class, 'reply']);
    Route::post('/admin-reply/{id}/{feedback_id}/user-reply', [FeedbackController::class, 'userReply']);
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
    Route::get('/feedbacks/{id}', [FeedbackController::class, 'show']);
    Route::put('/feedbacks/{id}', [FeedbackController::class, 'update']);
    Route::delete('/feedbacks/{id}', [FeedbackController::class, 'destroy']);
});

