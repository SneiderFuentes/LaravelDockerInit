<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Controllers\Api\MessageController;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Controllers\Api\CallController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Rutas para comunicación
// Route::prefix('communication')->group(function () {
//     // Mensajes
//     Route::post('/messages/whatsapp', [MessageController::class, 'sendWhatsapp']);
//     Route::post('/messages/webhook', [MessageController::class, 'webhook']);

//     // Llamadas
//     Route::post('/calls', [CallController::class, 'initiateCall']);
//     Route::post('/calls/webhook', [CallController::class, 'webhook']);
// });

// Webhook de MessageBird (sin autenticación)
//Route::post('/bird/webhook', [MessageController::class, 'webhook']);

// Main API routes are registered from domain-specific route files in:
// - src/BoundedContext/SubaccountManagement/Infrastructure/Http/routes.php
// - src/BoundedContext/CommunicationManagement/Infrastructure/Http/routes.php
// - src/BoundedContext/FlowManagement/Infrastructure/Http/routes.php
