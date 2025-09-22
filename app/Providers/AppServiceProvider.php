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
        // Autenticación de Horizon por clave secreta en URL
        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request) {
                // Verificar clave secreta en la URL: /horizon?key=tu_clave_secreta
                $secretKey = env('BIRD_API_KEY_VOICE');

                // Si no hay clave configurada, bloquear acceso
                if (empty($secretKey)) {
                    return false;
                }

                return $request->get('key') === $secretKey;
            });
        }
    }
}
