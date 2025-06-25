<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Core\BoundedContext\CommunicationManagement\Domain\Ports\MessageGatewayInterface;
use Core\BoundedContext\CommunicationManagement\Infrastructure\Adapters\Bird\BirdMessageAdapter;
use Illuminate\Http\Client\Factory as HttpClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Asegurarse de que MessageGatewayInterface se resuelve correctamente
        $this->app->singleton(MessageGatewayInterface::class, function ($app) {
            return new BirdMessageAdapter(
                $app->make(HttpClient::class),
                env('BIRD_API_KEY', ''),
                env('BIRD_API_URL', 'https://go.messagebird.com/1/messages')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
