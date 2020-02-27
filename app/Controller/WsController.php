<?php
declare(strict_types=1);

namespace App\Controller;

use App\Consumer\GroupChatMsgConsumer;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\DbConnection\Db;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

/**
 * Websocket主逻辑入口
 */
class WsController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        $data   = json_decode($frame->data, true);
        $event  = $data['event'] ?? '';
        $data   = $data['data'] ?? [];
        switch ($event) {
            case "group_chat":
                $this->groupChat($frame->fd, $data);
            break;
        }
    }

    public function groupChat($fd, $data)
    {
        $uid = $data['send_uid'] ?? ''; //发送者UID
        $name = $data['send_name'] ?? ''; //发送者名称
        $avatar = $data['send_avatar'] ?? ''; //发送者头像
        $msg = $data['send_msg'] ?? ''; //消息内容
        if (!$uid || !$msg) {
            return false;
        }
        
        //获取当前页面
        $connectInfo = $this->connectInfo($fd);
        $page = $connectInfo['current_page'];
        
        $userList = redis()->hGetAll("ws:connect:page:{$page}");
        if (!$userList) {
            return false;
        }

        //异步入队持久化聊天消息
        $params = $data;
        $params['current_page'] = $page;
        asyncQueueProduce(new GroupChatMsgConsumer($params), 0, 'group_chat_msg');

        //推送给当前页面下的所有用户
        foreach ($userList as $uid => $item) {
            $array = json_decode($item, true);
            $server_uri = $array['server_uri'] ?? '';
            $connect_fd = $array['connect_fd'] ?? '';
            ws_push($server_uri, $connect_fd, $uid, json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        
    }

    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        /**
         * 删除连接
         */
        $this->delConnect($fd);
        var_dump(sprintf("fd: %s is closed", $fd));
    }

    public function onOpen(WebSocketServer $server, Request $request): void
    {
        $params = $request->get;
        /**
         * 获取UID
         */
        $uid = $params['uid'] ?? '';
        if (!$uid) {
            //uid无效 关闭连接
            $server->close($request->fd);
        }
        /**
         * 当前页面
         */
        $page = $params['page'] ?? '';
        if (!$page) {
            //page无效 关闭连接
            $server->close($request->fd);
        }
        /**
         * 添加连接
         */
        $this->addConnect($uid, $request->fd, $page);

        $server->push($request->fd, 'success connect');
    }

    /**
     * 添加连接
     *
     * @param [type] $uid
     * @param [type] $fd
     * @param string $page
     * @return void
     */
    public function addConnect($uid, $fd, $page = '')
    {   
        $time = time();
        //进入页面统计
        $id = Db::table('page_record')->insertGetId([
            'uid'       => $uid,
            'page'      => $page,
            'entry_time'=> $time,
        ]);
        /**
         * 用户中心连接信息
         */
        $connect = json_encode([
            'server_uri'        => local_uri(), //当前ws服务器uri
            'current_page'      => $page,       //当前连接页面
            'connect_fd'        => $fd,         //连接ID
            'connect_time'      => $time,      //连接时间
            'record_id'         => $id,
        ]);
        redis()->hSet("ws:connect:user:center", $uid, $connect);
        /**
         * FD映射UID
         */
        redis()->hSet("ws:connect:fd:map:uid", ws_fd_hash_field($fd), $uid);
        /**
         * 当前页面连接信息
         */
        $currentPageConnect = json_encode([
            'server_uri'        => local_uri(), //当前ws服务器uri
            'connect_fd'        => $fd,         //连接ID
            'connect_time'      => $time,      //连接时间
        ]);
        redis()->hSet("ws:connect:page:{$page}", $uid, $currentPageConnect);
    }

    /**
     * 根据FD获取连接信息
     *
     * @param [type] $fd
     * @return void
     */
    public function connectInfo($fd)
    {
        /**
         * 根据FD获取UID
         */
        $uid = redis()->hGet("ws:connect:fd:map:uid", ws_fd_hash_field($fd));
        /**
         * 根据UID获取当前用户当前页面
         */
        $userConnect = redis()->hGet("ws:connect:user:center", $uid); 
        $userConnect = $userConnect ? json_decode($userConnect, true) : [];

        return [
            'uid'           => $uid,
            'current_page'  => $userConnect['current_page'] ?? '',
            'record_id'     => $userConnect['record_id'] ?? '',
        ];
    }

    /**
     * 删除连接
     *
     * @param [type] $fd
     * @return void
     */
    public function delConnect($fd)
    {
        /**
         * 根据FD获取连接信息
         */
        $connectInfo = $this->connectInfo($fd);
        $uid = $connectInfo['uid'];
        $page = $connectInfo['current_page'];
        $id = $connectInfo['record_id'];

        //更新离开时间
        Db::table('page_record')->where('id', $id)->update([
            'leave_time' => time(),
        ]);

        //剔除用户连接数据
        redis()->hDel("ws:connect:user:center", $uid);
        //剔除FD映射
        redis()->hDel("ws:connect:fd:map:uid", ws_fd_hash_field($fd));
        //剔除对应页面连接数据
        redis()->hDel("ws:connect:page:{$page}", $uid);

    }
}
