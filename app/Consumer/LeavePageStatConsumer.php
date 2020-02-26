<?php

declare(strict_types=1);

namespace App\Consumer;

use Hyperf\AsyncQueue\Job;
use Hyperf\DbConnection\Db;

class LeavePageStatConsumer extends Job
{
    public $params;

    public function __construct($params)
    {
        // 这里最好是普通数据，不要使用携带 IO 的对象，比如 PDO 对象
        $this->params = $params;
    }

    /**
     * 离开页面统计队列处理
     *
     * @author Jin<jinand10@163.com> 2020-01-10
     * @return void
     */
    public function handle()
    {
        //写你的DB逻辑
        var_dump($this->params);
    }
}
