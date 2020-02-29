<?php

declare(strict_types=1);

namespace App\Consumer;

use App\Service\WebSocket\MainService;
use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;

/**
 * 群聊消息持久化
 */
class GroupChatMsgConsumer extends Job
{
    public $params;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    /**
     * 群聊消息持久化处理
     *
     * @author Jin<jinand10@163.com> 2020-01-10
     * @return void
     */
    public function handle()
    {
        //DB持久化操作
        //$this->params 则是聊天数据
        $data = $this->params;

        $time = time();
        //存聊天统计
        $id = Db::table('dms_info')->insertGetId([
            'ower_id'       => $data['ower_id'],
			'uid'           => $data['uid'],
            'user_id'       => $data['from'],
            'fromNick'      => $data['fromNick'],
            'fromAvatar'    => $data['fromAvatar'],
            'chatroomId'    => $data['chatroomId'],
            'type'          => $data['type'],
            'text'          => $data['text'],
			'video_id'      => $data['video_id'],
			'time'          => $time,
			'custom'        => addslashes(json_encode($data['custom'])),
        ]);
    }
}
