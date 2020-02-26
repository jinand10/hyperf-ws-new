<?php
declare(strict_types=1);

namespace App\Controller\WebSocket;

use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Di\Annotation\Inject;
use Swoole\Http\Request;
use Swoole\Server;
use Swoole\Websocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;
use App\Service\WebSocket\MainService;

/**
 * Websocket主逻辑入口
 */
class MainController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{
    /**
     *
     * @Inject
     * @var MainService
     */
    private $mainService;

    /**
     * 消息监听事件
     *
     * @param WebSocketServer $server
     * @param Frame $frame
     * @return void
     */
    public function onMessage(WebSocketServer $server, Frame $frame): void
    {
        /**
         * *************前后端交互消息体格式规范*************
         * 
         * 前端请求-进入页面统计-JSON消息体说明
         * {
         *      "event": "entry_page_stat",
         *      "data": {
         *          "page": "index", //当前统计页面
         *      }
         * }
         * 
         * 前端接收-进入页面统计-JSON消息体说明
         * {
         *      "event": "entry_page_stat_res",
         *      "data": {
         *          "stat_id": "1", //当前统计ID
         *      }
         * }
         * 
         * 前端请求-离开页面统计-JSON消息体说明
         * {
         *      "event": "leave_page_stat",
         *      "data": {
         *          "page": "index", //当前统计页面
         *          "stat_id": "index", //当前统计ID 由进入时 后端响应的统计ID
         *      }
         * }
         * 
         * 前端请求-加入群聊-JSON消息体说明
         * {
         *      "event": "group_join",
         *      "data": {
         *          "topic": "group_live", //群聊订阅主题 类似房间组的唯一标识
         *          "join_uid": "1", //加入者UID
         *      }
         * }
         * 
         * 前端请求-群聊事件-JSON消息体说明
         * {
         *      "event": "group_chat",
         *      "data": {
         *          "topic": "group_live", //群聊订阅主题 类似房间组的唯一标识
         *          "send_uid": "1", //发送者UID
         *          "send_user_name": "张三", //发送者名字
         *          "send_user_avatar": "xxxx", //发送者头像
         *          "send_msg": "xxxx", //发送信息
         *      }
         * }
         * 
         * 前端接收-群聊事件-JSON消息体说明
         * {
         *      "event": "group_chat",
         *      "data": {
         *          "topic": "group_live", //群聊订阅主题 类似房间组的唯一标识
         *          "send_uid": "1", //发送者UID
         *          "send_user_name": "张三", //发送者名字
         *          "send_user_avatar": "xxxx", //发送者头像
         *          "send_msg": "xxxx", //发送信息
         *      }
         * }
         */
        $parse = json_decode($frame->data, true);
        if (is_array($parse)) {
            $event = $parse['event'] ?? '';
            $data = $parse['data'] ?? [];
            if ($event && $data) {
                switch ($event) {
                    case "entry_page_stat":
                        $this->mainService->entryPageStat($frame->fd, $data);
                        break;
                    case "leave_page_stat":
                        $this->mainService->leavePageStat($frame->fd, $data);
                        break;
                    case "group_join";
                        $this->mainService->joinGroup($frame->fd, $data);
                        break;
                    case "group_chat";
                        $this->mainService->chatInGroup($frame->fd, $data);
                        break;
                }
            }
        }
    }

    /**
     * 连接关闭事件
     *
     * @param Server $server
     * @param integer $fd
     * @param integer $reactorId
     * @return void
     */
    public function onClose(Server $server, int $fd, int $reactorId): void
    {
        //删除连接中心 
        $this->mainService->removeConnection($fd);
        var_dump(sprintf("fd: %s is closed", $fd));
    }

    /**
     * 初始连接事件
     *
     * @param WebSocketServer $server
     * @param Request $request
     * @return void
     */
    public function onOpen(WebSocketServer $server, Request $request): void
    {
        $params = $request->get;
        /**
         * websocket请求鉴权
         */
        $uid = $this->mainService->parseTokenGetUid($params['token'] ?? '');
        if (!$uid) {
            //token无效 关闭连接
            $server->close($request->fd);
        }   
        /**
         * 注册连接中心，Redis作为数据中心
         */
        if (! $this->mainService->registerConnection($uid, $request->fd)) {
            $server->close($request->fd);
        }
        $server->push($request->fd, 'success connect');
    }
}
