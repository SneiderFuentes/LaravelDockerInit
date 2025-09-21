<?php

use Illuminate\Support\Facades\Route;
use Core\Shared\Infrastructure\Http\Controllers\HealthCheckController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint for Docker
Route::get('/health', HealthCheckController::class);
