<?php

declare(strict_types=1);

namespace App\Consumer;

use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;

/**
 * 统计进入页面访问记录
 */
class UserStatEntryConsumer extends Job
{
    public $params;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    public function handle()
    {
        $ower_id = $this->params['ower_id'];
        $user_id = $this->params['user_id'];
        $model = $this->params['model'];
        $share_user_id = $this->params['share_user_id'];
        $content_id = $this->params['content_id'];
        $url = $this->params['url'];
        $entry_time = $this->params['entry_time'];

        try {
            Db::insert("insert into page_record(`ower_id`, `user_id`, `model`, `share_user_id`, `content_id`, `url`, `entry_time`) values({$ower_id}, {$user_id}, '{$model}', '{$share_user_id}', '{$content_id}', '{$url}', {$entry_time}) on duplicate key update `entry_time` = {$entry_time}");
        } catch (\Throwable $e) {
            logger()->error('进入页面统计异常, error: '.$e->getMessage());
        }
    }
}
