<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SurveyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes
Route::get('/survey-by-slug/{survey:slug}', [SurveyController::class, 'showForGuest']);
Route::post('/survey/{survey}/answer', [SurveyController::class, 'storeAnswer']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::resource('/survey', SurveyController::class);
    Route::get('/survey/answers/{survey}', [SurveyController::class, 'getAnswers']);


    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/logs', [DashboardController::class, 'getLogs']);
});


