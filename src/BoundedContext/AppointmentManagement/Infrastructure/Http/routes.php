<?php

use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/centers/{centerKey}')->group(function () {
    Route::get('appointments/pending', [AppointmentController::class, 'index']);
    Route::post('appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
});
