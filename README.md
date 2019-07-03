# laravel-swoole
基于 `swoole` 的 `laravel` `RPC`  扩展包， 目前提供了 `同步 RPC Client` , `RPC Server`


## 使用

- 条件
    - php >= 7.1
    - swoole >= 4.3
    - linux 系统环境
    - laravel >= 5.5
- 安装

        composer require jackdou/laravel-swoole
- 配置
    - 进入项目根目录 执行 `php artisan vender:publish`
    - 选择 `Provider: JackDou\Swoole\SwooleServiceProvider` 生成配置文件
    - 具体配置内容请根据实际情况在 `config/swoole.php` 中修改
    - 配置单个服务多节点的方式
        - 在 `config/server_node.php` 中可以根据自己的服务名称配置多个机器ip
        - 可设置权重和在线状态，权重越高分配到的请求也就越多
        - 配置的每个机器上都得按下面的方式启动相应的服务
- 启动服务
    - v1.1 及以下：
        - `php artisan swoole:server start/stop/reload` //开启/关闭/热重启 服务
    - v1.2 开始及以后
        - 支持单个机器配置一个业务服务和一个管理进程服务
        - `php artisan swoole:server server_name start/stop/reload` //开启、关闭、热重启 server_name服务
     - 使用 `ps aux | grep 'your server_name' ` 可以查看启动的进程信息
- fpm 中使用同步客户端访问微服务 demo
    - 编写微服务的业务代码:默认目录为 `app/Services`,新建 TestService.php 类并添加如下内容
        
            namespace App\Service;
           
            class TestService
            {
                //服务类都需要是静态属性
                public static function test($msg = 'hello world')
                {
                    return ['code' => 1, 'msg' => $msg];
                }
            }
    - 确保配置文件没问题后启动服务
    - 在项目任意可以访问的 `Controller` 中添加如下内容
                
                use JackDou\Swoole\Facade\Service;
                
                $res = Service::getInstance('swoole')->call('TestService::test', '你好')->getResult();
                dd($res);
    - 浏览器中访问观察效果。

- 建议
    - 默认是非守护进程方式启动， 推荐使用 `supervisor` 管理 `swoole` 进程
    - 使用中发现问题请联系 `jackdoujiang@qq.com` 或者提交 `issues`

- 注意
    - 如果改动配置或服务代码等记得 `reload` 或重启服务进程，不然不会生效
    - 暂不支持传递图片文件等资源文件
- 迭代计划(未实现)
    - 增加多功能服务下发配置方式并且推荐结合 jackdou/management 管理后台扩展使用
    - 支持基础 `web socket` 功能
    - 支持请求头，传递资源图片等功能
    - 支持单元测试和自动化测试
    - 服务节点检测，自动下线服务
    - 集成 `supervisor` 自动下发配置管理
