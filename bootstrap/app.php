<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Core\Console\Kernel as CoreConsoleKernel;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Core\Shared\Infrastructure\Http\Middleware\VerifyBirdSignature;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Http\Middleware\ValidateAsyncApiKey;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Http\Middleware\ValidateChatKey;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        // Core Laravel Providers
        App\Providers\AppServiceProvider::class,

        // Bounded Context Providers
        Core\BoundedContext\SubaccountManagement\Infrastructure\Providers\SubaccountServiceProvider::class,
        Core\BoundedContext\AppointmentManagement\Infrastructure\Providers\AppointmentServiceProvider::class,
        Core\BoundedContext\CommunicationManagement\Infrastructure\Providers\CommunicationServiceProvider::class,
        Core\BoundedContext\FlowManagement\Infrastructure\Providers\FlowServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'verify-bird-signature' => VerifyBirdSignature::class,
            'validate.async.key' => ValidateAsyncApiKey::class,
            'validate.chat.key' => ValidateChatKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->singleton(
    ConsoleKernelContract::class,
    CoreConsoleKernel::class
);

return $app;
