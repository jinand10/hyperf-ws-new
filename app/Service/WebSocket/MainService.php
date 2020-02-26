<?php
declare(strict_types=1);

namespace App\Service\WebSocket;

use App\Constants\WebSocket;
use App\Consumer\EntryPageStatConsumer;
use App\Consumer\LeavePageStatConsumer;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

class MainService
{
    /**
     * 根据host:port获取推送订阅者
     * @param string $uri
     * @return string
     */
    public function getPushChannelByUri(string $uri): string
    {
        return WebSocket::WEBSOCKET_PUSH_CHANNEL_PREFIX.$uri;
    }

    /**
     * 解析TOKEN获取UID
     * @param $token
     * @return int
     */
    public function parseTokenGetUid(string $token): string
    {
        if (!$token) {
            return 0;
        }
        //填充你的解析逻辑 返回UID
        //....代码逻辑
        return $token;
    }

    /**
     * 获取当前WS服务器的uri(host:port)
     * @return string
     */
    public function getLocalUri(): string
    {
        return (string)(swoole_get_local_ip()['eth0'] ?? '').':'.env('WS_SERVER_PORT', '9501');
    }

    /**
     * 获取当前WS服务器FD哈希的域
     * @param int $fd
     * @return string
     */
    public function getFdHashField(int $fd): string
    {
        return (string)($this->getLocalUri().'-'.$fd);
    }

    /**
     * 注册连接中心
     * @param string $uid
     * @param int $fd
     * @return bool
     */
    public function registerConnection(string $uid, int $fd): bool
    {
        /**
         * 根据连接池句柄获取Redis连接实例
         */
        $redis = ApplicationContext::getContainer()
            ->get(RedisFactory::class)
            ->get(WebSocket::WEBSOCKET_CONNECTION_DATA_DRIVER_POOL);
        $ret = $redis
            ->multi()
            //注册全局连接中心 UID哈希 1、可通过UID找到对应的连接信息 2、可通过这个哈希表计算全局在线人数
            ->hSet(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_UID_HASH, $uid, json_encode([
                'uri'           => $this->getLocalUri(), //当前ws服务器节点uri
                'fd'            => $fd, //连接ID
                'connect_time'  => time(), //连接时间
            ]))
            //注册全局连接中心 FD哈希 可通过当前HOST+FD找到 UID
            ->hSet(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_FD_HASH, $this->getFdHashField($fd), $uid)
            ->exec();
        return in_array(false, $ret) ? false : true;
    }

    /**
     * 删除连接中心
     * @param int $fd
     * @return bool
     */
    public function removeConnection(int $fd): bool
    {
        /**
         * 根据连接池句柄获取Redis连接实例
         */
        $redis = ApplicationContext::getContainer()
                ->get(RedisFactory::class)
                ->get(WebSocket::WEBSOCKET_CONNECTION_DATA_DRIVER_POOL);
        /**
         * 根据FD获取当前WS服务器的哈希域
         */
        $field = $this->getFdHashField($fd);
        /**
         * 根据FD从全局连接FD哈希获取UID
         */
        $uid = $redis->hGet(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_FD_HASH, $field);
        $ret = $redis
            ->multi()
            //根据UID剔除全局连接中心UID哈希
            ->hDel(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_UID_HASH, $uid)
            //根据FD哈希域剔除全局连接中心FD哈希
            ->hDel(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_FD_HASH, $field)
            ->exec();
        return in_array(false, $ret) ? false : true;
    }

    /**
     * 根据UID发送消息
     *
     * @param [type] $uid
     * @param [type] $msg
     * @return void
     */
    public function sendByUid($uid, $msg)
    {
        /**
         * 根据连接池句柄获取Redis连接实例
         */
        $redis = ApplicationContext::getContainer()
                ->get(RedisFactory::class)
                ->get(WebSocket::WEBSOCKET_CONNECTION_DATA_DRIVER_POOL);
        $connection = $redis->hget(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_UID_HASH, $uid);
        $connection = json_decode($connection, true);
        $uri = $connection['uri'] ?? '';
        $fd = $connection['fd'] ?? 0; 
        $channel = $this->getPushChannelByUri($uri);
        $redis->publish($channel, json_encode([
            'uri'   => $uri,
            'fd'    => $fd,
            'uid'   => $uid,
            'msg'   => $msg,
        ]));  
    }

    /**
     * 进入页面统计
     *
     * @param integer $fd
     * @param array $data
     * @return void
     */
    public function entryPageStat(int $fd, array $data)
    {
        $page = $data['page'] ?? '';
        if (!$page) {
            return;
        }
        /**
         * 根据连接池句柄获取Redis连接实例
         */
        $redis = ApplicationContext::getContainer()
                ->get(RedisFactory::class)
                ->get(WebSocket::WEBSOCKET_CONNECTION_DATA_DRIVER_POOL);
        /**
         * 根据FD获取当前WS服务器的哈希域
         */
        $field = $this->getFdHashField($fd);
        /**
         * 根据FD从全局连接FD哈希获取UID
         */
        $uid = $redis->hGet(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_FD_HASH, $field);

        //异步入队
        $params = [
            'uid'   => $uid, //不可以传FD 因为分布式的FD 可能不是唯一的
            'page'  => $page,
        ];
        asyncQueueProduce(new EntryPageStatConsumer($params), 0, 'entry_page_stat');
    }

    /**
     * 离开页面统计
     *
     * @param integer $fd
     * @param array $data
     * @return void
     */
    public function leavePageStat(int $fd, array $data)
    {
        $page = $data['page'] ?? '';
        $statId = $data['stat_id'] ?? '0';
        if (!$page || !$statId) {
            return;
        }
        /**
         * 根据连接池句柄获取Redis连接实例
         */
        $redis = ApplicationContext::getContainer()
                ->get(RedisFactory::class)
                ->get(WebSocket::WEBSOCKET_CONNECTION_DATA_DRIVER_POOL);
        /**
         * 根据FD获取当前WS服务器的哈希域
         */
        $field = $this->getFdHashField($fd);
        /**
         * 根据FD从全局连接FD哈希获取UID
         */
        $uid = $redis->hGet(WebSocket::GLOBAL_WEBSOCKET_CONNECTION_FD_HASH, $field);

        //异步入队
        $params = [
            'uid'       => $uid, //不可以传FD 因为分布式的FD 可能不是唯一的
            'page'      => $page,
            'stat_id'   => $statId,
        ];
        asyncQueueProduce(new LeavePageStatConsumer($params), 0, 'leave_page_stat');
    }

    public function joinGroup()
    {
        
    }

    public function chatInGroup()
    {

    }
}
