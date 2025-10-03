<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. The queue wait time is measured in seconds. The
    | threshold is the amount of seconds that a job may be waiting
    | before the event is dispatched.
    |
    */

    'waits' => [
        'redis:ai-vision' => 180,       // 3 minutos
        'redis:ai-logic' => 120,        // 2 minutos
        'redis:notifications' => 60,    // 1 minuto
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to exclude certain processing intensive jobs from the list.
    |
    */

    'silenced' => [
        // App\Jobs\ProcessPodcast::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'maxProcesses' => 1,
            'minProcesses' => 1,
            'tries' => 1,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'ai-vision-supervisor' => [
                'connection' => 'redis',
                'queue' => ['ai-vision'],
                'balance' => 'simple', // Procesos fijos para jobs largos y pesados
                'processes' => 2,      // Empezar con 2, monitorear carga de la API
                'tries' => 3,          // El Job define esto, pero lo ponemos por claridad
                'timeout' => 240,      // 4 mins, > al timeout del job (180s)
                'memory' => 256,       // Más memoria para el procesamiento de archivos
                'nice' => 10,          // Menor prioridad para no afectar a la app principal
            ],
            'ai-logic-supervisor' => [
                'connection' => 'redis',
                'queue' => ['ai-logic'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 4,   // Escalado moderado para llamadas a API
                'tries' => 3,
                'timeout' => 180,      // 3 mins, > al timeout del job más largo (120s)
                'memory' => 128,
                'nice' => 5,
            ],
            'notifications-supervisor' => [
                'connection' => 'redis',
                'queue' => ['notifications'],
                'balance' => 'auto',
                'minProcesses' => 2,
                'maxProcesses' => 10,  // Pueden correr muchos en paralelo
                'tries' => 3,
                'timeout' => 240,      // 4 mins, > al timeout del job más largo (180s)
                'memory' => 128,
            ],
            'appointment-updates-supervisor' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'simple', // Procesos fijos para evitar concurrencia
                'processes' => 1,      // SOLO 1 proceso para evitar race conditions
                'tries' => 3,
                'timeout' => 120,      // 2 mins, suficiente para actualizaciones
                'memory' => 64,
            ],
        ],

        'local' => [
            'ai-vision-supervisor' => [
                'connection' => 'redis',
                'queue' => ['ai-vision'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 240,
                'memory' => 256,
            ],
            'ai-logic-supervisor' => [
                'connection' => 'redis',
                'queue' => ['ai-logic'],
                'balance' => 'simple',
                'processes' => 1,
                'tries' => 3,
                'timeout' => 180,
                'memory' => 128,
            ],
            'notifications-supervisor' => [
                'connection' => 'redis',
                'queue' => ['notifications'], // Removemos 'default' para evitar conflictos
                'balance' => 'simple',
                'processes' => 2,
                'tries' => 3,
                'timeout' => 240,
                'memory' => 128,
            ],
            'appointment-updates-supervisor' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'simple',
                'processes' => 1,      // SOLO 1 proceso en local también
                'tries' => 3,
                'timeout' => 120,
                'memory' => 64,
            ],
        ],
    ],
];
