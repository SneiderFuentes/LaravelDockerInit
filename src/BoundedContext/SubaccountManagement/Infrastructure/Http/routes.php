<?php

use Core\BoundedContext\SubaccountManagement\Infrastructure\Http\Controllers\SubaccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/subaccounts')->group(function () {
    Route::get('/', [SubaccountController::class, 'index']);
    Route::get('/{key}', [SubaccountController::class, 'show']);
});
