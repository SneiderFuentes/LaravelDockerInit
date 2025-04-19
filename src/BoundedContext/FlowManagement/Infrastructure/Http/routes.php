<?php

use Illuminate\Support\Facades\Route;
use Core\BoundedContext\FlowManagement\Infrastructure\Http\Controllers\FlowController;

Route::prefix('api/flows')->group(function () {
    Route::post('/trigger', [FlowController::class, 'triggerFlow']);
    Route::get('/', [FlowController::class, 'listFlows']);
    Route::get('/channel-types', [FlowController::class, 'getChannelTypes']);
});
