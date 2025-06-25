<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\Providers;

use Core\BoundedContext\CommunicationManagement\Domain\Repositories\CallRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Adapters\Bird\BirdMessageAdapter;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\EloquentCallRepository;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\EloquentMessageRepository;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Services\MessageBirdCommunicationService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Client\Factory as HttpClient;

class CommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // El binding de MessageGatewayInterface ya está en AppServiceProvider
        // para asegurar que se cargue temprano en el ciclo de aplicación

        // Registro de repositorios
        $this->app->bind(
            MessageRepositoryInterface::class,
            EloquentMessageRepository::class
        );

        $this->app->bind(
            CallRepositoryInterface::class,
            EloquentCallRepository::class
        );

        // Registro de servicios de dominio
        $this->app->bind(CommunicationService::class, function ($app) {
            return new MessageBirdCommunicationService(
                config('messagebird.api_key', env('BIRD_API_KEY', '')),
                $app->make(MessageRepositoryInterface::class),
                $app->make(CallRepositoryInterface::class),
                $app->make(MessageGatewayInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Registrar el middleware de Bird para webhooks


        // Cargar rutas
        $this->loadRoutesFrom(base_path('src/BoundedContext/CommunicationManagement/Infrastructure/Http/routes.php'));

        // Publicar configuración
        $this->publishes([
            __DIR__ . '/../../config/messagebird.php' => config_path('messagebird.php'),
        ], 'config');

        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
