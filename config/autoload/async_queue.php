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
    /**
     * 进入页面统计
     */
    'entry_page_stat' => [
        'driver' => App\Kernel\AsyncQueue\Driver\RedisDriver::class,
        'channel' => 'queue:entry:page:stat',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => env('PROCESS_NUM_ENTRY_PAGE_STAT', 1),
        'redis_pool' => 'async_queue',
    ],
    /**
     * 离开页面统计
     */
    'leave_page_stat' => [
        'driver' => App\Kernel\AsyncQueue\Driver\RedisDriver::class,
        'channel' => 'queue:leave:page:stat',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => env('PROCESS_NUM_LEAVE_PAGE_STAT', 1),
        'redis_pool' => 'async_queue',
    ],
];
