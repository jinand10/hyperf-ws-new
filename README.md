**基于hyperf的分布式websocket服务**

> 注意：
* 当前生命周期是处于协程环境 所有的实例化操作 都需要谨慎操作，包括类的属性的使用、不要使用静态、不要使用类属性
* websocket的服务启动需要在config/server.php 配置server的host和端口监听
* 需要在config/routes.php注册websocket路由
* socket连接将会转发到控制器下面的WsController 处理逻辑
* 连接时会加入redis 的连接中心、断开时候会从redis剔除

> 环境要求：
* PHP >= 7.2
* Swoole PHP 扩展 >= 4.4，并关闭了 Short Name
* OpenSSL PHP 扩展
* JSON PHP 扩展
* PDO PHP 扩展 （如需要使用到 MySQL 客户端）
* Redis PHP 扩展 （如需要使用到 Redis 客户端）
* composer依赖

> 启动服务：
* git clone https://github.com/jinand10/hyperf-ws-new.git 
* cd hyperf-ws-new && composer install
* mv .env.sample .env 修改ENV配置文件 检查WS服务器配置，DB连接配置 Redis连接配置
* 最后 php bin/hyperf.php start

> WS连接说明：
* WS客户端测试地址：http://www.easyswoole.com/wstool.html
* WS地址：ws://IP:PORT?uid=1&page=group_chat
* 参数说明：uid：用户ID, page：当前页面 例如群聊页面group_chat

说明：
* 页面统计：只需要初始化WS连接 ws://IP:PORT?uid=用户ID&page=对应页面
* 群聊页面：
    初始化WS连接：ws://IP:PORT?uid=用户ID&page=群聊页面
    WS-JSON消息体：
        * 例子：{"event":"group_chat","data":{"send_uid": "1", "send_msg": "我吃饱啦"}}
        * event：指定群聊事件 group_chat
        * data.send_uid：发送者UID
        * data.send_msg：发送者消息
        * data.send_name： 发送者名称
        * data.send_avatar： 发送者头像

    

