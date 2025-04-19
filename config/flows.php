<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flow Registry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the flow management system.
    |
    */

    // Available channel types
    'channels' => [
        'sms' => [
            'name' => 'SMS',
            'max_message_length' => 160,
            'enabled' => true,
        ],
        'whatsapp' => [
            'name' => 'WhatsApp',
            'max_message_length' => 4096,
            'enabled' => true,
        ],
        'voice' => [
            'name' => 'Llamada de voz',
            'enabled' => false,
        ],
    ],

    // Default flow configuration
    'defaults' => [
        'default_channel' => 'whatsapp',
        'error_message' => 'Lo sentimos, hubo un problema procesando su solicitud. Por favor, intente nuevamente más tarde.',
    ],

    // Flow handlers to auto-register
    'handlers' => [
        'confirm_appointment' => \Core\BoundedContext\FlowManagement\Infrastructure\FlowHandlers\ConfirmAppointmentFlowHandler::class,
        'cancel_appointment' => \Core\BoundedContext\FlowManagement\Infrastructure\FlowHandlers\CancelAppointmentFlowHandler::class,
    ],

    // Webhook configuration
    'webhooks' => [
        'verify_signature' => true,
        'max_payload_size' => 5000, // in KB
    ],
];
