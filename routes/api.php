<?php

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReferralController;
use App\Http\Controllers\API\ReferralSettingController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\TripController;
use App\Http\Controllers\API\TripScheduleController;
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
Route::post('/admin/register', [AdminController::class, 'adminRegister']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
    Route::get('/admin/users/{email}', [AdminController::class, 'getAllUsersByEmail']);
    Route::get('/admin/getProfile', [AdminController::class, 'getProfile']);
    Route::get('/admin/trips', [AdminController::class, 'getAllTrips']);
    Route::get('/admin/trips/{trip_id}', [AdminController::class, 'getAllTripsPerId']);
    Route::get('/admin/completed-trips', [AdminController::class, 'getAllCompletedTrips']);
    Route::get('/admin/pending-trips', [AdminController::class, 'getPendingTrips']);
    Route::get('/admin/accepted-trips', [AdminController::class, 'getAllAcceptedTrips']);
    Route::get('/admin/failed-trips', [AdminController::class, 'getAllFailedTrips']);
    Route::get('/admin/feedbacks', [AdminController::class, 'getAllFeedbacks']);

});

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
    Route::put('/accept-trip', [TripController::class, 'acceptTrip']);
    Route::post('/decline-trip', [TripController::class, 'declineTrip']);
    Route::get('/all-user-trips-as-driver', [TripController::class, 'getUsersTrip']);
    Route::get('/all-user-trips-as-passenger', [TripController::class, 'getUsersTripAsPassenger']);
    Route::get('/get-trips-by-id/{tripId}', [TripController::class, 'getTripDetailsById']);
    Route::post('/create-scheduleTrip', [TripScheduleController::class, 'scheduleTrip']);
    Route::get('/get-scheduleTrip-by-id/{id}', [TripScheduleController::class, 'getTripById']);
    Route::get('/get-all-scheduleTrip-perUser', [TripScheduleController::class, 'getTrip']);
    Route::post('/update-scheduleTrip/{id}', [TripScheduleController::class, 'updateTrip']);
    Route::delete('/delete-scheduleTrip/{id}', [TripScheduleController::class, 'deleteTrip']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::post('/feedback/{id}/reply', [FeedbackController::class, 'reply']);
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
    Route::get('/adminReplyPerUser', [FeedbackController::class, 'AdminReplyPerUser']);
    Route::get('/adminReplyPerUserById/{id}', [FeedbackController::class, 'AdminReplies']);
    Route::get('/feedbacks/{id}', [FeedbackController::class, 'show']);
    Route::put('/feedbacks/{id}', [FeedbackController::class, 'update']);
    Route::delete('/feedbacks/{id}', [FeedbackController::class, 'destroy']);
    Route::post('/admin-reply/{id}/{feedback_id}/user-reply', [FeedbackController::class, 'userReply']);

    // Referrals
    Route::get('/generate-link', [ReferralController::class, 'generateReferralUrl']);
    Route::get('/get-refPoint-per-user', [ReferralController::class, 'getAllReferral']);

    Route::post('/set-ref', [ReferralSettingController::class, 'createReferral']);
    Route::put('/update-ref/{referralId}', [ReferralSettingController::class, 'updateReferral']);
    Route::get('/get-ref-settings/perAdmin', [ReferralSettingController::class, 'getAllReferralSettings']);

    //Payment
    Route::post('/make-payment/{tripId}', [PaymentController::class, 'inviteUserToTripPayment']);
    Route::get('/get-payment', [PaymentController::class, 'getPayment']);

    // Reporting
    Route::get('/get-report', [ReportController::class, 'getTripReport']);
});

