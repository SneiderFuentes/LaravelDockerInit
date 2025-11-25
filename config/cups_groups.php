<?php

return [
    'groups' => [
        'CONSULTA DE NEUROLOGIA' => [
            'cups' => ['890274', '890374'],
            'tarifa' => 53560,
            'min' => 325,
            'ref' => 361,
            'max' => 397,
            'valor_mes' => 19335160
        ],
        'CONSULTA DE ELECTROENCEFALOGRAMA' => [
            'cups' => ['891402', '891901', '891402-1', '891402PED', '891901-1', '891901PED', '891401', '891401PED'],
            'tarifa' => 198465,
            'min' => 140,
            'ref' => 156,
            'max' => 172,
            'valor_mes' => 30960555
        ],
        'CONSULTA DE BLOQUEOS' => [
            'cups' => ['053106', '053105', '053111'],
            'tarifa' => 111303,
            'min' => 55,
            'ref' => 64,
            'max' => 67,
            'valor_mes' => 6789478
        ],
        'CONSULTA DE APLICACIÓN DE SUSTANCIA' => [
            'cups' => ['861411', '48201'],
            'tarifa' => 270831,
            'min' => 16,
            'ref' => 18,
            'max' => 20,
            'valor_mes' => 4874958
        ],
        'CONSULTA DE POLISOMNOGRAFIA' => [
            'cups' => ['891704', '891703', '891704-1', '891704PED', '891703-1', '891703PED'],
            'tarifa' => 612433,
            'min' => 42,
            'ref' => 47,
            'max' => 57,
            'valor_mes' => 35070488
        ],
        'CONSULTA DE OTROS PROCEDIMIENTOS' => [
            'cups' => ['891515', '891514', '930820', '891511', '891509', '930860', '891530', '952303', '954626', '952302', '930103', '930821', '954624', '954625'],
            'tarifa' => 37613,
            'min' => 839,
            'ref' => 932,
            'max' => 932, //1026,
            'valor_mes' => 35070488
        ],
    ],

    // Configuración de validación
    'validation' => [
        'enabled' => true,
        'check_monthly_limits' => true,
        'default_max_limit' => 1026, // Límite por defecto si no se especifica
    ]
];
