<?php

use App\Http\Controllers\API\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\StudentController;
use App\Http\Controllers\API\JobController;
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

// Auth guided routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('/updateProfile', [AuthController::class, 'updateProfile']);
    Route::put('/userImage', [AuthController::class, 'profileImage']);
    Route::get('/ops', [AuthController::class, 'geocodeAddress']);
    Route::put('/handleFileUpload/{userId}', [AuthController::class, 'handleFileUpload']);
    Route::resource('property', PropertyController::class);
    Route::get('/getUserProperty',[PropertyController::class, 'getUserProperty']);
    Route::resource('student', StudentController::class);
    Route::get('/getUserSchool',[StudentController::class, 'getUserSchool']);
    Route::resource('job', JobController::class);
    Route::get('/getUserJob',[JobController::class, 'getUserJob']);
});
