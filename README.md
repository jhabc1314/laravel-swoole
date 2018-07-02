# laravel-swoole
laravel swoole扩展 目前提供了Server和WebSocket两种模式，使用一个artisan命令即可开启一个服务，省去了写代码的烦恼

## 使用
- 安装

        composer require jackdou/laravel-swoole
- 配置

        php artisan vender:publish //生成配置文件
   具体配置根据实际情况在 config/swoole.php中配置
- 启动
    
    进入项目根目录
     - `php artisan swoole:server` //开启一个普通服务
     - `php artisan swoole:socket` //开启一个webSocket服务
