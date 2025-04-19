<?php

use Illuminate\Support\Facades\Route;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Http\Controllers\WebhookController;

Route::prefix('api/webhooks')->group(function () {
    Route::post('/bird', [WebhookController::class, 'handleBirdWebhook'])
        ->middleware('verify-bird-signature');
});
