<?php

declare(strict_types=1);

use Hyperf\AsyncQueue\JobInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Http\Message\ServerRequestInterface;

if (!function_exists('di')) {
    /**
     * Finds an entry of the container by its identifier and returns it.
     * @param null|mixed $id
     * @return mixed|\Psr\Container\ContainerInterface
     */
    function di($id = null)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }
        return $container;
    }
}

if (!function_exists('stdoutLogger')) {
    /**
     * Finds an stdoutLogger of the container
     * @return \Hyperf\Contract\StdoutLoggerInterface|mixed
     */
    function stdoutLogger()
    {
        return ApplicationContext::getContainer()->get(\Hyperf\Contract\StdoutLoggerInterface::class);
    }
}

if (!function_exists('logger')) {
    /**
     * Finds an logger of the container
     * @param string $name
     * @param string $group
     * @return \Psr\Log\LoggerInterface
     */
    function logger($name = 'app', $group = 'default')
    {
        return ApplicationContext::getContainer()->get(\Hyperf\Logger\LoggerFactory::class)->get($name, $group);
    }
}

if (!function_exists('redis')) {
    /**
     * Finds an redis object of the container
     * @param string $poolName
     * @return \Hyperf\Redis\RedisProxy|Redis
     */
    function redis($poolName = 'default')
    {
        return ApplicationContext::getContainer()->get(\Hyperf\Redis\RedisFactory::class)->get($poolName);
    }
}


if (!function_exists('clientIp')) {
    /**
     * 获取客户端IP
     * @return string
     */
    function clientIp()
    {
        /** @var Hyperf\HttpServer\Contract\RequestInterface $request */
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $clientIp = $request->getHeader('x-real-ip');
        if ($clientIp) {
            return (string) (current($clientIp));
        }
        /** @var Psr\Http\Message\ServerRequestInterface $serverRequestInterface */
        $serverRequestInterface = Context::get(ServerRequestInterface::class);
        $serverParams = $serverRequestInterface->getServerParams();
        return (string) $serverParams['remote_addr'] ?? '';
    }
}

if (!function_exists('asyncQueueProduce')) {
    /**
     * Push a job to async queue.
     */
    function asyncQueueProduce(JobInterface $job, int $delay = 0, string $key = 'default'): bool
    {
        $driver = ApplicationContext::getContainer()->get(Hyperf\AsyncQueue\Driver\DriverFactory::class)->get($key);
        return $driver->push($job, $delay);
    }
}

if (!function_exists('local_uri')) {
    /**
     * 获取当前WS服务器URI
     */
    function local_uri(): string
    {
        return (string)(swoole_get_local_ip()['eth0'] ?? '').':'.env('WS_SERVER_PORT', '9501');
    }
}

if (!function_exists('ws_fd_hash_field')) {
    /**
     * WS连接FD哈希域
     */
    function ws_fd_hash_field($fd): string
    {
        return local_uri().':'.$fd;
    }
}


if (!function_exists('ws_push_channel')) {
    /**
     * WS-redis订阅渠道
     */
    function ws_push_channel($uri): string
    {
        return 'ws:push:channel:'.$uri;
    }
}

if (!function_exists('ws_push')) {
    /**
     * WS推送
     */
    function ws_push($uri, $fd, $uid, $msg): bool
    {
        if (!$uri || !$fd) {
            return false;
        }
        $push = json_encode([
            'uri'   => $uri,
            'fd'    => $fd,
            'uid'   => $uid,
            'msg'   => $msg,
        ]);
        return (bool)(redis()->publish(ws_push_channel($uri), $push));
    }
}


