<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MessageBird API Key
    |--------------------------------------------------------------------------
    |
    | Your MessageBird API key. Obtener desde el panel de control de MessageBird.
    |
    */
    'api_key' => env('BIRD_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret para validar que los webhooks provengan de MessageBird.
    |
    */
    'webhook_secret' => env('BIRD_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Webhook URL
    |--------------------------------------------------------------------------
    |
    | URL base para los webhooks de MessageBird.
    |
    */
    'webhook_url' => env('BIRD_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Sender Information
    |--------------------------------------------------------------------------
    |
    | Información del remitente para SMS y llamadas.
    |
    */
    'originator' => env('BIRD_ORIGINATOR', 'MyAppts'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica para mensajes de WhatsApp.
    |
    */
    'whatsapp' => [
        'channel_id' => env('BIRD_WHATSAPP_CHANNEL_ID', 'whatsapp'),
        'enabled' => env('BIRD_WHATSAPP_ENABLED', true),
        'business_name' => env('BIRD_WHATSAPP_BUSINESS_NAME', 'MyAppts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica para mensajes SMS.
    |
    */
    'sms' => [
        'originator' => env('BIRD_SMS_ORIGINATOR', env('BIRD_ORIGINATOR', 'MyAppts')),
        'enabled' => env('BIRD_SMS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración específica para llamadas de voz.
    |
    */
    'voice' => [
        'number' => env('BIRD_VOICE_NUMBER'),
        'enabled' => env('BIRD_VOICE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flow IDs
    |--------------------------------------------------------------------------
    |
    | IDs de los flujos configurados en MessageBird para diferentes acciones.
    |
    */
    'flows' => [
        'appointment_reminder' => env('BIRD_FLOW_CONFIRM_APPOINTMENT'),
        'appointment_confirmation' => env('BIRD_FLOW_BOOK_VOICE'),
        'appointment_cancellation' => env('BIRD_FLOW_CANCEL_APPOINTMENT'),
        'create_client' => env('BIRD_FLOW_CREATE_CLIENT'),
        'send_results' => env('BIRD_FLOW_SEND_RESULTS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Templates
    |--------------------------------------------------------------------------
    |
    | Plantillas preaprobadas para WhatsApp Business API.
    | Cada plantilla debe estar aprobada en el portal de MessageBird/CM.com
    |
    */
    'templates' => [
        'appointment_reminder' => [
            'name' => 'appointment_reminder',
            'language' => 'es',
            'namespace' => env('BIRD_TEMPLATE_NAMESPACE', ''),
            'params' => ['patient_name', 'doctor_name', 'appointment_date', 'appointment_time', 'clinic_name']
        ],
        'appointment_confirmation' => [
            'name' => 'appointment_confirmation',
            'language' => 'es',
            'namespace' => env('BIRD_TEMPLATE_NAMESPACE', ''),
            'params' => ['patient_name', 'appointment_date', 'appointment_time', 'clinic_name']
        ],
        'appointment_cancellation' => [
            'name' => 'appointment_cancellation',
            'language' => 'es',
            'namespace' => env('BIRD_TEMPLATE_NAMESPACE', ''),
            'params' => ['patient_name', 'appointment_date', 'clinic_name']
        ],
        'appointment_rescheduled' => [
            'name' => 'appointment_rescheduled',
            'language' => 'es',
            'namespace' => env('BIRD_TEMPLATE_NAMESPACE', ''),
            'params' => ['patient_name', 'old_date', 'new_date', 'new_time', 'clinic_name']
        ],
        'results_available' => [
            'name' => 'results_available',
            'language' => 'es',
            'namespace' => env('BIRD_TEMPLATE_NAMESPACE', ''),
            'params' => ['patient_name', 'test_type', 'clinic_name']
        ],
    ],
];
