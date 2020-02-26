<?php

declare(strict_types=1);

namespace App\Consumer;

use App\Service\WebSocket\MainService;
use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;

class EntryPageStatConsumer extends Job
{
    public $params;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    /**
     * 进入页面统计队列处理
     *
     * @author Jin<jinand10@163.com> 2020-01-10
     * @return void
     */
    public function handle()
    {
        //此处写你的DB逻辑
        //.....
        //.....
        var_dump($this->params);
        //插入记录之后 返回主键ID 则响应给前端 走socket通道
        $statId = 1;
        /** @var MainService $mainService*/
        $mainService = make(MainService::class);
        $msg = json_encode([
            'event' => 'entry_page_stat_res',
            'data'  => [
                'stat_id' => $statId
            ],
        ]);
        $mainService->sendByUid($this->params['uid'], $msg);
    }
}
