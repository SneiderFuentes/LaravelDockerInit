<?php

namespace Core\Providers;

use Core\BoundedContext\AppointmentManagement\Domain\Repositories\AppointmentRepositoryInterface;
use Core\BoundedContext\AppointmentManagement\Infrastructure\Adapters\AppointmentRepositoryFactory;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\CallGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Adapters\Bird\BirdCallAdapter;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Adapters\Bird\BirdMessageAdapter;
use Core\BoundedContext\FlowManagement\Domain\Contracts\FlowRegistryInterface;
use Core\BoundedContext\FlowManagement\Infrastructure\Adapters\InMemoryFlowRegistry;
use Core\BoundedContext\SubaccountManagement\Domain\Repositories\SubaccountRepositoryInterface;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent\EloquentSubaccountRepository;
use Core\BoundedContext\SubaccountManagement\Infrastructure\Persistence\Eloquent\EloquentSubaccount;
use Core\Shared\Infrastructure\Http\Middleware\VerifyBirdSignature;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\ServiceProvider;

class BoundedContextServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Subaccount Management services
        $this->app->bind(SubaccountRepositoryInterface::class, function ($app) {
            return new EloquentSubaccountRepository(
                new EloquentSubaccount()
            );
        });

        // Register Appointment Management services
        $this->app->singleton(AppointmentRepositoryInterface::class, function ($app) {
            return $app->make(AppointmentRepositoryFactory::class)->create();
        });

        // Register Communication Management services
        $this->app->bind(MessageGatewayInterface::class, function ($app) {
            return new BirdMessageAdapter(
                $app->make(HttpClient::class),
                config('services.bird.api_key'),
                config('services.bird.api_url')
            );
        });

        $this->app->bind(CallGatewayInterface::class, function ($app) {
            return new BirdCallAdapter(
                $app->make(HttpClient::class),
                config('services.bird.api_key'),
                config('services.bird.api_url')
            );
        });

        // Register Flow Management services
        $this->app->singleton(FlowRegistryInterface::class, function ($app) {
            return new InMemoryFlowRegistry();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('verify-bird-signature', VerifyBirdSignature::class);

        // Register the flow handlers
        $this->registerFlowHandlers();

        // Include domain-specific routes
        $this->loadRoutesFrom(base_path('src/BoundedContext/SubaccountManagement/Infrastructure/Http/routes.php'));
        $this->loadRoutesFrom(base_path('src/BoundedContext/CommunicationManagement/Infrastructure/Http/routes.php'));
        $this->loadRoutesFrom(base_path('src/BoundedContext/FlowManagement/Infrastructure/Http/routes.php'));
    }

    /**
     * Register all flow handlers
     */
    private function registerFlowHandlers()
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
