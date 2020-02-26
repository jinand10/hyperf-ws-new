<?php

declare(strict_types=1);

namespace App\Kernel\AsyncQueue\Driver;

use Hyperf\AsyncQueue\Driver\RedisDriver as DriverRedisDriver;
use Psr\Container\ContainerInterface;

class RedisDriver extends DriverRedisDriver
{
    protected $redisPool;

    public function __construct(ContainerInterface $container, $config)
    {
        parent::__construct($container, $config);
        $this->redisPool = $config['redis_pool'] ?? 'default';
        $this->redis = $container->get(\Hyperf\Redis\RedisFactory::class)->get($this->redisPool);
    }
}
