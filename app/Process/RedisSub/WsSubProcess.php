<?php
declare(strict_types=1);

namespace App\Process\RedisSub;

use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

use Hyperf\Redis\RedisFactory;
use App\Constants\WebSocket;
use Hyperf\WebSocketServer\Sender;

/**
 * @Process
 */
class WsSubProcess extends AbstractProcess
{
    public function handle(): void
    {
        $channel = ws_push_channel(local_uri());
        var_dump(sprintf("开始订阅 channel: %s", $channel));
        redis()->subscribe([$channel], [$this, 'push']);
    }

    public function push($redis, $chan, $data)
    {
        $data = json_decode($data, true);
        $fd = (int)($data['fd'] ?? 0);
        $msg = $data['msg'] ?? '';
        if ($fd && $msg) {
            var_dump(1);
            $this->container->get(Sender::class)->push($fd, $msg);
        }
    }
}
