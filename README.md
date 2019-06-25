# laravel-swoole
基于 `swoole` 的 `laravel` 扩展， 目前提供了 `Tcp Server` 和 `WebSocket` 两种功能


## 使用
- <span style="color:red">目前还处于开发调试阶段，请勿使用</span>
- 安装

        composer require jackdou/laravel-swoole
- 配置

        php artisan vender:publish 
        //选择 Provider: JackDou\Swoole\SwooleServiceProvider
        //生成配置文件
   具体配置内容请根据实际情况在 config/swoole.php 中修改
- 启动
   
    进入项目根目录
     - `php artisan swoole:server start/stop/reload` //开启/关闭/重启 tcp server
     - `php artisan swoole:socket` //开启一个webSocket服务
- 发送请求
    
        use JackDou\Swoole\Facade\Service;
        ...
        $res = Service::getInstance()
            ->call('TestService::getInfo', $param1,$param2...)
            ->getResult();
       //注意：调用的方法必须为静态，默认类的存放目录为app/Service，可以在swoole.php内修改
    
- 建议

    - 推荐使用 `supervisor` 管理 `swoole` 进程
    - 使用问题请联系 `jh1139209675@gmail.com`
