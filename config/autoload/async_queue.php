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
     * 群聊消息持久化
     */
    'group_chat_msg' => [
        'driver' => App\Kernel\AsyncQueue\Driver\RedisDriver::class,
        'channel' => 'queue:group:chat:msg',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => env('PROCESS_NUM_GROUP_CHAT_MSG', 1),
        'redis_pool' => 'async_queue',
    ],
    /**
     * 统计进入页面访问记录
     */
    'user_stat_entry' => [
        'driver' => App\Kernel\AsyncQueue\Driver\RedisDriver::class,
        'channel' => 'queue:user:stat:entry',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => env('PROCESS_NUM_USER_STAT_ENTRY', 1),
        'redis_pool' => 'async_queue',
    ],
    /**
     * 统计离开页面访问记录
     */
    'user_stat_leave' => [
        'driver' => App\Kernel\AsyncQueue\Driver\RedisDriver::class,
        'channel' => 'queue:user:stat:leave',
        'timeout' => 2,
        'retry_seconds' => 5,
        'handle_timeout' => 10,
        'processes' => env('PROCESS_NUM_USER_STAT_LEAVE', 1),
        'redis_pool' => 'async_queue',
    ],
];
