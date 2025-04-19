<?php

namespace Core\BoundedContext\CommunicationManagement\Infrastructure\ServiceProviders;

use Core\BoundedContext\CommunicationManagement\Domain\Repositories\CallRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Repositories\MessageRepositoryInterface;
use Core\BoundedContext\CommunicationManagement\Domain\Services\CommunicationService;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\EloquentCallRepository;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Persistence\Eloquent\EloquentMessageRepository;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Services\MessageBirdCommunicationService;
use Illuminate\Support\ServiceProvider;

class CommunicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
                config('messagebird.api_key'),
                $app->make(MessageRepositoryInterface::class),
                $app->make(CallRepositoryInterface::class)
            );
        });
    }

    public function boot(): void
    {
        // Publicar configuración
        $this->publishes([
            __DIR__ . '/../../config/messagebird.php' => config_path('messagebird.php'),
        ], 'config');

        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
