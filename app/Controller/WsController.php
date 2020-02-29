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
        logger()->info('接受消息: '.$frame->data);

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
        logger()->info('接受群聊消息: '.json_encode($data, JSON_UNESCAPED_UNICODE));

        $uid = $data['from'] ?? ''; //发送者UID
        $name = $data['fromNick'] ?? ''; //发送者名称
        $avatar = $data['fromAvatar'] ?? ''; //发送者头像
        $msg = $data['text'] ?? ''; //消息内容

        
        //获取当前页面
        $connectInfo = $this->connectInfo($fd);
        $model = $connectInfo['current_model'] ?? '';
		
	//	$model = 'zhibo:'.$data['video_id'];
		
		logger()->info($fd.',get current_model: '.$model);	


        
        //当前页面下的所有在线用户
        $userList = redis()->hGetAll("ws:connect:model:{$model}");
        if (!$userList) {
			logger()->info('user list  is empty: '.$model);	
            return false;
        }

        //异步入队持久化聊天消息
        $params = $data;
        $params['current_model'] = $model;
        asyncQueueProduce(new GroupChatMsgConsumer($params), 0, 'group_chat_msg');

        //推送给当前页面下的所有用户
        foreach ($userList as $uid_key => $item) {
            $array = json_decode($item, true);
            $server_uri = $array['server_uri'] ?? '';
            $connect_fd = $array['connect_fd'] ?? '';
			ws_push($server_uri, $connect_fd, $uid_key, json_encode($data, JSON_UNESCAPED_UNICODE));
			logger()->info($connect_fd.',user key : '.$uid_key);
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
    	
        $key = $params['key'] ?? '';
		logger()->info('onOpen: '.$key);
        if (!$key) {
            //key无效 关闭连接
            $server->close($request->fd);
			logger()->info('key无效 关闭连接: '.$key);
            return;
        }
        $params = json_decode(auth_code($key), true);
        if (!$params) {
            //非法请求
			logger()->info('非法请求: '.$key);
            $server->close($request->fd);
            return;
        }
        $user_id = $params['user_id'] ?? 0;
        $ower_id = $params['ower_id'] ?? 0;
        $model = $params['model'] ?? '';
        if ($user_id <= 0 || $ower_id <= 0 || !$model) {
            //非法请求
            $server->close($request->fd);
			logger()->info('非法请求2: '.$key);
            return;
        }

        /**
         * 添加连接
         */
        $this->addConnect($request->fd, $params);

        $server->push($request->fd, 'success connect');
    }

    /**
     * 添加连接
     *
     */
    public function addConnect($fd, $params)
    {   
        $user_id = $params['user_id'];
        $ower_id = $params['ower_id'];
        $model = $params['model'];
        $share_user_id = $params['share_user_id'] ?? 0;
        $content_id = $params['id'] ?? 0;
        $url = $params['url'] ?? '';
        $type = $params['type'] ?? '';
		
		$curr_model = $model.':'.$content_id;
		
        $time = time();
        $record_id = 0;
		if ($type == 'user_stat') {
			//进入页面统计
			$record_id = Db::table('page_record')->insertGetId([
				'ower_id'       => $ower_id,
				'user_id'       => $user_id,
				'model'         => $model,
				'share_user_id' => $share_user_id,
				'content_id'    => $content_id,
				'url'           => $url,
				'entry_time'    => $time,
			]);
		}
        /**
         * 唯一用户标识
         */
        $unique_uid = $ower_id.'-'.$user_id;
        /**
         * 用户中心连接信息
         */
        $connect = json_encode([
            'server_uri'        => local_uri(), //当前ws服务器uri
            'current_model'     => $curr_model, //当前连接页面
            'connect_fd'        => $fd,         //连接ID
            'connect_time'      => $time,       //连接时间
            'record_id'         => $record_id,
        ]);
        redis()->hSet("ws:connect:user:center", $unique_uid, $connect);
        /**
         * FD映射UID
         */
        redis()->hSet("ws:connect:fd:map:uid", ws_fd_hash_field($fd), $unique_uid);
        /**
         * 当前页面连接信息
         */
        $currentPageConnect = json_encode([
            'server_uri'        => local_uri(), //当前ws服务器uri
            'connect_fd'        => $fd,         //连接ID
            'connect_time'      => $time,      //连接时间
        ]);
        redis()->hSet("ws:connect:model:{$curr_model}", $unique_uid, $currentPageConnect);

        var_dump('加入连接中心成功');
		logger()->info('加入连接中心成功', $params);	
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
         * 根据FD获取唯一用户标识UID
         */
        $unique_uid = redis()->hGet("ws:connect:fd:map:uid", ws_fd_hash_field($fd));
        if (!$unique_uid) {
            return [];
        }
        /**
         * 根据UID获取当前用户当前页面
         */
        $userConnect = redis()->hGet("ws:connect:user:center", $unique_uid); 
        $userConnect = $userConnect ? json_decode($userConnect, true) : [];

        return [
            'unique_uid'    => $unique_uid,
            'current_model' => $userConnect['current_model'] ?? '',
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
        if (!$connectInfo) {
            return;
        }
        $unique_uid = $connectInfo['unique_uid'];
        $model = $connectInfo['current_model'];
        $id = $connectInfo['record_id'];

        //更新离开时间
        $time = time();
        $res = Db::update("update page_record set leave_time = {$time}, stay_time = {$time}-entry_time where id = {$id}");
        //剔除用户连接数据
        redis()->hDel("ws:connect:user:center", $unique_uid);
        //剔除FD映射
        redis()->hDel("ws:connect:fd:map:uid", ws_fd_hash_field($fd));
        //剔除对应页面连接数据
        redis()->hDel("ws:connect:model:{$model}", $unique_uid);

    }
}
