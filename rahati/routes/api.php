<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CenterController;
use App\Http\Controllers\API\AppointmentController;
use App\Http\Controllers\API\ConsultationController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\MealOptionController;
use App\Http\Controllers\API\AccommodationController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\TransportationRequestController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ServiceCapacityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // User routes
    Route::apiResource('users', UserController::class);
    Route::get('/my-patients', [UserController::class, 'myPatients']);
    Route::get('/my-patients-detailed', [UserController::class, 'myPatientsDetailed']);
    Route::get('/patient-details/{id}', [UserController::class, 'patientDetails']);

    // Center routes
    Route::apiResource('centers', CenterController::class);

    // Appointment routes
    Route::apiResource('appointments', AppointmentController::class);

    // Consultation routes
    Route::apiResource('consultations', ConsultationController::class);

    // Payment routes
    Route::apiResource('payments', PaymentController::class);

    // Meal Option routes
    Route::apiResource('meal-options', MealOptionController::class);

    // Accommodation routes
    Route::apiResource('accommodations', AccommodationController::class);

    // Room routes
    Route::apiResource('rooms', RoomController::class);

    // Transportation Request routes
    Route::apiResource('transportation-requests', TransportationRequestController::class);

    // Feedback routes
    Route::apiResource('feedback', FeedbackController::class);

    // Notification routes
    Route::apiResource('notifications', NotificationController::class);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Service Capacity routes
    Route::apiResource('service-capacity', ServiceCapacityController::class);
});
