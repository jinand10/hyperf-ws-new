<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

return [
    'default' => [
        'host' => env('REDIS_HOST', 'localhost'),
        'auth' => env('REDIS_AUTH', null),
        'port' => (int) env('REDIS_PORT', 6379),
        'db' => (int) env('REDIS_DB', 0),
        'pool' => [
            'min_connections' => (int) env('REDIS_MIN_CONNECTIONS', 1),
            'max_connections' => (int) env('REDIS_MIN_CONNECTIONS', 20),
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
        ],
        'options' => [
            Redis::OPT_READ_TIMEOUT => -1,
        ],
    ],
    'async_queue' => [
        'host' => env('QUEUE_REDIS_HOST', 'localhost'),
        'auth' => env('QUEUE_REDIS_AUTH', null),
        'port' => (int) env('QUEUE_REDIS_PORT', 6379),
        'db' => (int) env('QUEUE_REDIS_DB', 0),
        'pool' => [
            'min_connections' => (int) env('QUEUE_REDIS_MIN_CONNECTIONS', 1),
            'max_connections' => (int) env('QUEUE_REDIS_MIN_CONNECTIONS', 20),
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('QUEUE_REDIS_MAX_IDLE_TIME', 60),
        ],
    ],
    
];
