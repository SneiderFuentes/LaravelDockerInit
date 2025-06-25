<?php

return [
    // Core Laravel Providers
    App\Providers\AppServiceProvider::class,
    // Package Providers
    Laravel\Horizon\HorizonServiceProvider::class,

    // Bounded Context Providers (independientes)
    Core\BoundedContext\SubaccountManagement\Infrastructure\Providers\SubaccountServiceProvider::class,
    Core\BoundedContext\AppointmentManagement\Infrastructure\Providers\AppointmentServiceProvider::class,
    Core\BoundedContext\CommunicationManagement\Infrastructure\Providers\CommunicationServiceProvider::class,
    Core\BoundedContext\FlowManagement\Infrastructure\Providers\FlowServiceProvider::class,
];
