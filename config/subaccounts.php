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
        'datosipsndx' => [
            'name' => 'Neuro Electro Diagnostico del llano',
            'connection' => 'mysql_datosipsndx',
            'connections' => [
                'default' => 'mysql_datosipsndx',
                'doctors' => 'mysql_medicos',
                'accounting' => 'mysql_contabilidad'
            ],
            'api_header' => env('FLOW_APPOINMENT_WEBHOOK_API_HEADER', 'X-API-KEY'),
            'api_key' => env('FLOW_APPOINMENT_WEBHOOK_API_KEY', 'valor_de_api_key'),
            'tables' => [
                'appointments' => [
                    'table' => 'citas',
                    'mapping' => [
                        'id' => 'IdCita',
                        'request_date' => 'FechaSolicitud',
                        'date' => 'FeCita',
                        'time_slot' => 'FechaCita',
                        'doctor_id' => 'IdMedico',
                        'patient_id' => 'NumeroPaciente',
                        'created_by' => 'CreadoPor',
                        'entity' => 'Entidad',
                        'canceled' => 'Cancelada',
                        'user_request_date' => 'FechaPideUsuario',
                        'agenda_id' => 'Agenda',
                        'cancel_date' => 'FechaCancelacion',
                        'confirmed' => 'Confirmada',
                        'confirmation_date' => 'FechaConfirmacion',
                        'confirmation_channel' => 'MedioConfirmacion',
                        'confirmation_channel_id' => 'IdMedioConfirmacion',
                        'fulfilled' => 'Cumplida',
                        'observations' => 'Observaciones',
                        'remonte' => 'Remonte',
                    ],
                ],
                'schedules' => [
                    'table' => 'tblagendas',
                    'mapping' => [
                        'id' => 'RegistroNo',
                        'doctor_document' => 'IdTercero',
                        'name' => 'NombreAgenda'
                    ],
                ],
                'schedule_configs' => [
                    'table' => 'citas_conf',
                    'mapping' => [
                        'id' => 'IdConfig',
                        'doctor_document' => 'IdMedico',
                        'appointment_duration' => 'DuracionCita',
                        'is_active' => 'Activo',
                        'agenda_id' => 'IdAgenda',
                        'sessions_per_appointment' => 'SesionesxCita',
                        // Días laborales (0=domingo, 1=lunes, ..., 6=sábado)
                        'works_sunday' => 'Trabaja0',
                        'works_monday' => 'Trabaja1',
                        'works_tuesday' => 'Trabaja2',
                        'works_wednesday' => 'Trabaja3',
                        'works_thursday' => 'Trabaja4',
                        'works_friday' => 'Trabaja5',
                        'works_saturday' => 'Trabaja6',
                        // Horarios mañana
                        'morning_start_sunday' => 'HInicioM0',
                        'morning_start_monday' => 'HInicioM1',
                        'morning_start_tuesday' => 'HInicioM2',
                        'morning_start_wednesday' => 'HInicioM3',
                        'morning_start_thursday' => 'HInicioM4',
                        'morning_start_friday' => 'HInicioM5',
                        'morning_start_saturday' => 'HInicioM6',
                        'morning_end_sunday' => 'HFinalM0',
                        'morning_end_monday' => 'HFinalM1',
                        'morning_end_tuesday' => 'HFinalM2',
                        'morning_end_wednesday' => 'HFinalM3',
                        'morning_end_thursday' => 'HFinalM4',
                        'morning_end_friday' => 'HFinalM5',
                        'morning_end_saturday' => 'HFinalM6',
                        // Horarios tarde
                        'afternoon_start_sunday' => 'HInicioT0',
                        'afternoon_start_monday' => 'HInicioT1',
                        'afternoon_start_tuesday' => 'HInicioT2',
                        'afternoon_start_wednesday' => 'HInicioT3',
                        'afternoon_start_thursday' => 'HInicioT4',
                        'afternoon_start_friday' => 'HInicioT5',
                        'afternoon_start_saturday' => 'HInicioT6',
                        'afternoon_end_sunday' => 'HFinalT0',
                        'afternoon_end_monday' => 'HFinalT1',
                        'afternoon_end_tuesday' => 'HFinalT2',
                        'afternoon_end_wednesday' => 'HFinalT3',
                        'afternoon_end_thursday' => 'HFinalT4',
                        'afternoon_end_friday' => 'HFinalT5',
                        'afternoon_end_saturday' => 'HFinalT6'
                    ],
                ],
                'working_days' => [
                    'table' => 'tblexepciondias',
                    'mapping' => [
                        'id' => 'RegistroNo',
                        'doctor_document' => 'IdTercero',
                        'date' => 'Fecha',
                        'morning_enabled' => 'JornadaM',
                        'afternoon_enabled' => 'JornadaT',
                        'agenda_id' => 'IdAgenda',
                        'exception_type' => 'TipoExcepcion'
                    ],
                ],
                'patients' => [
                    'table' => 'pacientes',
                    'mapping' => [
                        'id' => 'NumeroPaciente',
                        'document_type' => 'TipoID',
                        'document_number' => 'IDPaciente',
                        'first_surname' => 'Apellido1',
                        'second_surname' => 'Apellido2',
                        'first_name' => 'Nombre1',
                        'second_name' => 'Nombre2',
                        'full_name' => 'NCompleto',
                        'affiliation_type' => 'TipoAfiliacion',
                        'user_type' => 'TipoUsuario',
                        'birth_date' => 'FechaNacimiento',
                        'gender' => 'SexoPaciente',
                        'address' => 'Direccion',
                        'city_code' => 'Municipio',
                        'phone' => 'Telefono',
                        'zone' => 'Zona',
                        'occupation' => 'Ocupacion',
                        'email' => 'CorreoE',
                        'created_at' => 'FechaCreado',
                        'updated_at' => 'FechaModificado',
                        'created_by' => 'CreadoPor',
                        'updated_by' => 'ModificadoPor',
                        'entity_code' => 'EntidadPaciente',
                        'level' => 'Nivel',
                        'marital_status' => 'EstadoCivil',
                        'birth_place' => 'LugarNacimiento',
                        'education_level' => 'Escolaridad',
                        'country_code' => 'codPaisOrigen',
                    ],
                ],
                'procedures' => [
                    'table' => 'cups_procedimientos',
                    'mapping' => [
                        'id' => 'id',
                        'code' => 'codigo_cups',
                        'name' => 'nombre',
                        'description' => 'descripcion',
                        'specialty_id' => 'especialidad_id',
                        'service_id' => 'servicio_id',
                        'service_name' => 'servicio',
                        'preparation' => 'preparacion',
                        'address' => 'direccion',
                        'video_url' => 'video_url',
                        'audio_url' => 'audio_url',
                        'type' => 'tipo',
                        'required_spaces' => 'espacios_requeridos',
                        'specific_schedule_id' => 'horario_especifico_id',
                        'assignment_flow_id' => 'flujo_asignacion_id',
                        'is_active' => 'activo',
                        'created_at' => 'created_at',
                        'updated_at' => 'updated_at'
                    ],
                ],
                'specific_schedules' => [
                    'table' => 'cups_horarios_especificos',
                    'mapping' => [
                        'id' => 'id',
                        'procedure_id' => 'cup_id',
                        'hour' => 'hora',
                        'is_active' => 'activo',
                        'created_at' => 'created_at',
                        'updated_at' => 'updated_at'
                    ],
                ],
                'cup_medico' => [
                    'table' => 'cup_medico',
                    'mapping' => [
                        'id' => 'id',
                        'cup_id' => 'cup_id',
                        'doctor_document' => 'doctor_documento',
                        'doctor_full_name' => 'doctor_nombre_completo',
                        'is_active' => 'activo',
                        'created_at' => 'created_at',
                        'updated_at' => 'updated_at',
                    ],
                ],
                'pxcita' => [
                    'table' => 'pxcita',
                    'mapping' => [
                        'id' => 'RegistroNo',
                        'created_at' => 'FechaCreado',
                        'appointment_id' => 'IdCita',
                        'cup_code' => 'CUPS',
                        'quantity' => 'Cantidad',
                        'unit_value' => 'VrUnitario',
                        'service_id' => 'IdServicio',
                        'billed' => 'Facturado',
                        'package_id' => 'IdPaquete',
                    ],
                ],
                'entities' => [
                    'table' => 'entidades',
                    'mapping' => [
                        'id' => 'NoRegistro',
                        'code' => 'IDEntidad',
                        'name' => 'NombreEntidad',
                        'price_type' => 'TipoPrecio',
                        'is_active' => 'contratoactivo',
                    ],
                ],
                'municipios' => [
                    'table' => 'municipios',
                    'mapping' => [
                        'id' => 'id',
                        'department_code' => 'cod_departamento',
                        'department_name' => 'departamento',
                        'municipality_code' => 'cod_municipio',
                        'municipality_name' => 'municipio',
                    ],
                ],
                'soat_codes' => [
                    'table' => 'codigossoat',
                    'mapping' => [
                        'cup_code' => 'CodigoCUPS',
                        'iss_code' => 'CodigoISS',
                        // Tarifas 01 a 40
                        'tariff_01' => 'Tarifa01',
                        'tariff_02' => 'Tarifa02',
                        'tariff_03' => 'Tarifa03',
                        'tariff_04' => 'Tarifa04',
                        'tariff_05' => 'Tarifa05',
                        'tariff_06' => 'Tarifa06',
                        'tariff_07' => 'Tarifa07',
                        'tariff_08' => 'Tarifa08',
                        'tariff_09' => 'Tarifa09',
                        'tariff_10' => 'Tarifa10',
                        'tariff_11' => 'Tarifa11',
                        'tariff_12' => 'Tarifa12',
                        'tariff_13' => 'Tarifa13',
                        'tariff_14' => 'Tarifa14',
                        'tariff_15' => 'Tarifa15',
                        'tariff_16' => 'Tarifa16',
                        'tariff_17' => 'Tarifa17',
                        'tariff_18' => 'Tarifa18',
                        'tariff_19' => 'Tarifa19',
                        'tariff_20' => 'Tarifa20',
                        'tariff_21' => 'Tarifa21',
                        'tariff_22' => 'Tarifa22',
                        'tariff_23' => 'Tarifa23',
                        'tariff_24' => 'Tarifa24',
                        'tariff_25' => 'Tarifa25',
                        'tariff_26' => 'Tarifa26',
                        'tariff_27' => 'Tarifa27',
                        'tariff_28' => 'Tarifa28',
                        'tariff_29' => 'Tarifa29',
                        'tariff_30' => 'Tarifa30',
                        'tariff_31' => 'Tarifa31',
                        'tariff_32' => 'Tarifa32',
                        'tariff_33' => 'Tarifa33',
                        'tariff_34' => 'Tarifa34',
                        'tariff_35' => 'Tarifa35',
                        'tariff_36' => 'Tarifa36',
                        'tariff_37' => 'Tarifa37',
                        'tariff_38' => 'Tarifa38',
                        'tariff_39' => 'Tarifa39',
                        'tariff_40' => 'Tarifa40',
                    ],
                ],
            ],
        ],
    ],
];
