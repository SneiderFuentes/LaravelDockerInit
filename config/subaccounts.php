<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subaccount Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the subaccount management system.
    |
    */

    // Default settings for new subaccounts
    'defaults' => [
        'api_rate_limit' => 100, // Requests per minute
        'storage_limit' => 1024, // MB
        'max_appointments' => 5000,
        'enabled_features' => [
            'appointments',
            'communications',
            'webhooks',
        ],
    ],

    // Validation rules for subaccount fields
    'validation' => [
        'key' => 'required|string|min:3|max:50|regex:/^[a-z0-9\-_]+$/i|unique:subaccounts,key',
        'name' => 'required|string|min:3|max:100',
        'config' => 'sometimes|array',
    ],

    // Security settings
    'security' => [
        'auto_rotation_period' => 30, // Days
        'password_policy' => [
            'min_length' => 10,
            'require_uppercase' => true,
            'require_number' => true,
            'require_special' => true,
        ],
    ],

    // API Integration settings
    'api' => [
        'auth_methods' => ['api_key', 'oauth2'],
        'default_auth_method' => 'api_key',
        'token_expiry' => 60, // Minutes
    ],

    'centers' => [
        'center_a' => [
            'name' => 'Clínica San José',
            'connection' => 'mysql_center_a',
            'tables' => [
                'appointments' => [
                    'table' => 'citas_programadas',
                    'mapping' => [
                        'id' => 'id_cita',
                        'scheduled_at' => 'fecha_hora',
                        'status' => 'estado',
                        'patient_id' => 'paciente_ref',
                        'notes' => 'observaciones',
                    ],
                ],
                'patients' => [
                    'table' => 'pacientes',
                    'mapping' => [
                        'id' => 'id_paciente',
                        'name' => 'nombre_completo',
                        'phone' => 'telefono',
                    ],
                ],
            ],
        ],
        'center_b' => [
            'name' => 'Hospital El Progreso',
            'connection' => 'mysql_center_b',
            'tables' => [
                'appointments' => [
                    'table' => 'appointments_b',
                    'mapping' => [
                        'id' => 'appt_id',
                        'scheduled_at' => 'scheduled_on',
                        'status' => 'status_code',
                        'patient_id' => 'p_id',
                        'notes' => 'additional_info',
                    ],
                ],
                'patients' => [
                    'table' => 'patients_b',
                    'mapping' => [
                        'id' => 'patient_id',
                        'name' => 'full_name',
                        'phone' => 'phone_number',
                    ],
                ],
            ],
        ],
    ],
];
