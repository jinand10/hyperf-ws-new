<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class WebSocket extends AbstractConstants
{

    /**
     * 连接中心驱动句柄
     */
    const WEBSOCKET_CONNECTION_DATA_DRIVER_POOL = 'default';

    /**
     * 推送订阅者channel前缀
     */
    const WEBSOCKET_PUSH_CHANNEL_PREFIX = 'websocket:push:channel:';

    /**
     * 全局连接中心UID哈希
     */
    const GLOBAL_WEBSOCKET_CONNECTION_UID_HASH = 'global:websocket:connect:uid:hash';

    /**
     * 全局连接中心FD哈希
     */
    const GLOBAL_WEBSOCKET_CONNECTION_FD_HASH = 'global:websocket:connect:fd:hash';

    /**
     * 页面列表
     */
    const PAGE_LIST = 'page:list';
}
