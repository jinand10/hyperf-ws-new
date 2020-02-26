<?php
declare(strict_types=1);

namespace App\Process\RedisSub;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Di\Annotation\Inject;

use Hyperf\Redis\RedisFactory;
use App\Constants\WebSocket;
use App\Service\WebSocket\MainService;
use Hyperf\WebSocketServer\Sender;

/**
 * @Process
 */
class WsSubProcess extends AbstractProcess
{
    /**
     *
     * @Inject
     * @var MainService
     */
    private $mainService;

    public function handle(): void
    {
        $redis = $this->container
            ->get(RedisFactory::class)
            ->get(WebSocket::WEBSOCKET_CONNECTION_DATA_DRIVER_POOL);
        $channel = $this->mainService->getPushChannelByUri($this->mainService->getLocalUri());
        var_dump(sprintf("开始订阅 channel: %s", $channel));
        $redis->subscribe([$channel], [$this, 'push']);
    }

    public function push($redis, $chan, $data)
    {
        $data = json_decode($data, true);
        $fd = (int)($data['fd'] ?? 0);
        $msg = $data['msg'] ?? '';
        if ($fd && $msg) {
            $this->container->get(Sender::class)->push($fd, $msg);
        }
    }
}
