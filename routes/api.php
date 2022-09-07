<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Group routes Mentor
Route::controller(OrderController::class)->group(function () {
    Route::get('/orders', 'index');
    Route::post('/orders', 'create');
    // Route::put('/mentors/{id}', 'update');
    // Route::delete('/mentors/{id}', 'destroy');
});

Route::controller(WebhookController::class)->group(function () {
    Route::post('/webhook', 'midtransHandler');
    // Route::put('/mentors/{id}', 'update');
    // Route::delete('/mentors/{id}', 'destroy');
});
