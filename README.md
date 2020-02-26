基于hyperf的分布式websocket服务

注意：
1、当前生命周期是处于协程环境 所有的实例化操作 都需要谨慎操作，包括类的属性的使用、不要使用静态、不要使用类属性
2、websocket的服务启动需要在config/server.php 配置server的host和端口监听
3、需要在config/routes.php注册websocket路由
4、socket连接将会转发到控制器下面的mainController 处理逻辑
5、连接时会加入redis 的连接中心、断开时候会从redis剔除

业务逻辑：
1、前端启动程序的时候 需要与服务器建立websocket连接
2、进入对应的页面，则发送对应的event给服务器，服务器监听到不同的event进行不同的逻辑处理
3、访问记录链路：进入页面->发送(event=entry_page_stat事件)->服务器处理之后发送响应(event=entry_page_stat_res事件)->前端此时拿到响应回来的统计ID---->离开页面->发送(event=leave_page_stat事件，data带上统计ID)
4、聊天的链路：进入聊天页面->发送加入群聊(event=group_join事件)->用户发送消息->聊天事件(event=group_chat事件)