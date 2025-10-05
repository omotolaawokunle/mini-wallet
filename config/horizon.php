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

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

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

    'middleware' => ['web', 'auth:sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 30,
        'redis:transfers' => 15,
        'redis:notifications' => 60,
        'redis:low' => 120,
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
        'recent' => 1440,        // Keep for 24 hours
        'pending' => 1440,       // Keep for 24 hours
        'completed' => 60,       // Keep for 1 hour
        'recent_failed' => 10080, // Keep for 1 week
        'failed' => 10080,       // Keep for 1 week
        'monitored' => 10080,    // Keep for 1 week
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    |
    | Silencing a job will instruct Horizon to not place the job in the list
    | of completed jobs within the Horizon dashboard. This setting may be
    | used to fully remove any noisy jobs from the completed jobs list.
    |
    */

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    'silenced_tags' => [
        // 'notifications',
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

    'fast_termination' => true,

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

    'memory_limit' => 512,

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
        // Transfers supervisor
        'transfers' => [
            'connection' => 'redis',
            'queue' => ['transfers'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 10,
            'maxProcesses' => 50,
            'balanceMaxShift' => 10,
            'balanceCooldown' => 2,
            'maxTime' => 0,
            'maxJobs' => 1000,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],

        // Default queue for non-critical jobs
        'default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'simple',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 2,
            'maxProcesses' => 10,
            'balanceMaxShift' => 2,
            'balanceCooldown' => 5,
            'maxTime' => 0,
            'maxJobs' => 500,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],

        // Notifications and non-critical tasks
        'notifications' => [
            'connection' => 'redis',
            'queue' => ['notifications'],
            'balance' => 'simple',
            'autoScalingStrategy' => 'size',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 10,
            'maxTime' => 0,
            'maxJobs' => 100,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 300,
            'nice' => 10,
        ],

        // Low priority background tasks
        'low' => [
            'connection' => 'redis',
            'queue' => ['low'],
            'balance' => 'false',
            'autoScalingStrategy' => 'size',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 15,
            'maxTime' => 0,
            'maxJobs' => 50,
            'memory' => 128,
            'tries' => 2,
            'timeout' => 600,
            'nice' => 15,
        ],
    ],

    'environments' => [
        'production' => [
            // Transfers - handle bulk load
            'transfers' => [
                'minProcesses' => 20,
                'maxProcesses' => 100,
                'balanceMaxShift' => 20,
                'balanceCooldown' => 1,
            ],

            // Default queue
            'default' => [
                'minProcesses' => 5,
                'maxProcesses' => 20,
            ],

            // Notifications
            'notifications' => [
                'minProcesses' => 2,
                'maxProcesses' => 10,
            ],

            // Low priority
            'low' => [
                'minProcesses' => 1,
                'maxProcesses' => 5,
            ],
        ],

        'staging' => [
            'transfers' => [
                'minProcesses' => 5,
                'maxProcesses' => 30,
            ],

            'default' => [
                'minProcesses' => 2,
                'maxProcesses' => 10,
            ],

            'notifications' => [
                'minProcesses' => 1,
                'maxProcesses' => 3,
            ],

            'low' => [
                'minProcesses' => 1,
                'maxProcesses' => 2,
            ],
        ],

        'local' => [
            'transfers' => [
                'minProcesses' => 2,
                'maxProcesses' => 5,
            ],

            'default' => [
                'minProcesses' => 1,
                'maxProcesses' => 3,
            ],

            'notifications' => [
                'minProcesses' => 1,
                'maxProcesses' => 2,
            ],

            'low' => [
                'minProcesses' => 1,
                'maxProcesses' => 1,
            ],
        ],
    ],
];
