<?php

declare(strict_types=1);

namespace Core\BoundedContext\FlowManagement\Infrastructure\Providers;

use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowRegistryInterface;
use Core\BoundedContext\FlowManagement\Infrastructure\Adapters\InMemoryFlowRegistry;
use Illuminate\Support\ServiceProvider;

final class FlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Flow Management services
        $this->app->singleton(FlowRegistryInterface::class, function ($app) {
            return new InMemoryFlowRegistry();
        });
    }

    public function boot(): void
    {
        // Register the flow handlers
        $this->registerFlowHandlers();

        // Include domain-specific routes
        $this->loadRoutesFrom(base_path('src/BoundedContext/FlowManagement/Infrastructure/Http/routes.php'));
    }

    /**
     * Register all flow handlers
     */
    private function registerFlowHandlers(): void
    {
        // This could use automatic discovery, but for simplicity, we'll list them
        $flowHandlers = [
            \Core\BoundedContext\FlowManagement\Infrastructure\FlowHandlers\ConfirmAppointmentFlowHandler::class,
            \Core\BoundedContext\FlowManagement\Infrastructure\FlowHandlers\CancelAppointmentFlowHandler::class,
        ];

        $registry = $this->app->make(FlowRegistryInterface::class);

        foreach ($flowHandlers as $handlerClass) {
            $handler = $this->app->make($handlerClass);
            $registry->register($handler);
        }
    }
}
