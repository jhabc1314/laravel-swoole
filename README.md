# laravel-swoole
基于 `swoole` 的 `laravel` `RPC`  扩展包， 目前提供了 `RPC Client` , `RPC Server` 和集群机器节点管理 `server` 功能


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
    - 如果使用 `management` 管理后台，需要将服务发现方式改为 `2`
- 启动服务
    - v1.1 及以下：
        - `php artisan swoole:server start/stop/reload` //开启/关闭/热重启 服务
    - v1.2 开始及以后
        - 支持单个机器配置一个业务服务和一个管理进程服务
        - `php artisan swoole:server server_name start/stop/reload` //开启、关闭、热重启 server_name服务
     - 使用 `ps aux | grep 'your server_name' ` 可以查看启动的进程信息
- fpm 中使用同步客户端访问微服务 demo
    - 编写微服务的业务代码:默认目录为 `app/Services`,新建 TestService.php 类并添加如下内容
        
            namespace App\Services;
           
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
                //getResult 参数为超时时间，单位秒，默认0.5秒。如果服务在0.5秒内没有返回结果则返回null
                $res = Service::getInstance('swoole')->call('TestService::test', '你好')->getResult(0.5);
                dd($res);
    - 浏览器中访问观察效果。

- 若你的 `laravel` 项目使用 `swooletw/laravel-swoole` 等类似组件将框架常驻进程或者在服务的项目代码里可以使用协程异步客户端提高性能
- `Coroutine\Client` 使用 demo

        namespace App\Services;
      
        class TestService
        {
            //测试协程客户端
            public static function test_co($msg = 'hello world')
            {
                //常规调用
                try {
                    $co = new SwooleCoClient();
                    $co->getInstance('swoole')
                        ->call('TestService::test')
                        ->onSuccess(function ($response_data) {
                            //$response_data 响应数据，请求的service返回的数据结构
                            //在这里处理请求成功返回数据以后的逻辑
                        })->onFail(function ($err_msg, $err_code) {
                            //请求失败了，在这里记录日志等
                        })->run();
                } catch (\Throwable $throwable) {
                    //寻找服务等发生错误，在这里看原因
                }
              
                //在一个请求内对一个服务多次调用
                try {
                    $co = new SwooleCoClient();
                    $swoole = $co->getInstance();//同一个服务只用指定一次
                    $swoole->call('TestService::test')
                        ->onSuccess(function ($response_data) {
                            //$response_data 响应数据，请求的service返回的数据结构
                            //在这里处理请求成功返回数据以后的逻辑
                        })->onFail(function ($msg, $code) {
                            //请求失败了，在这里记录日志等
                        });
                    $swoole->call('TestService::test_node')
                        ->onSuccess(function ($response_data) {
                            //$response_data 响应数据，请求的service返回的数据结构
                            //在这里处理请求成功返回数据以后的逻辑
                        })->onFail(function ($msg, $code) {
                            //请求失败了，在这里记录日志等

                        });
                    $swoole->run();
                } catch (\Throwable $throwable) {
                    //寻找服务等错误，在这里查看
                }

                //在一个请求内对不同服务调用
                try {
                    $co = new SwooleCoClient();
                     
                    $swoole = $co->getInstance('swoole');
                    $swoole->call('TestService::test')
                        ->onSuccess(function ($response_data) {
                            //$response_data 响应数据，请求的service返回的数据结构
                            //在这里处理请求成功返回数据以后的逻辑
                        })->onFail(function ($msg, $code) {
                            //请求失败了，在这里记录日志等
                        });
                    //重新指定另一个服务
                    $swoole = $co->getInstance('swoole2');
                    $swoole->call('TestService::test_node')
                        ->onSuccess(function ($response_data) {
                            //$response_data 响应数据，请求的service返回的数据结构
                            //在这里处理请求成功返回数据以后的逻辑
                        })->onFail(function ($msg, $code) {
                            //请求失败了，在这里记录日志等

                        });
                    $swoole->run();
                } catch (\Throwable $throwable) {
                    //寻找服务等错误，在这里查看
                }
                
                //请求成功一个以后请求另一个服务
                try {
                    $co = new SwooleCoClient();
                    $swoole = $co->getInstance('swoole');
                    $swoole->call('TestService::test')
                        ->onSuccess(function ($response_data) {
                            //$response_data 响应数据，请求的service返回的数据结构
                            //在这里处理请求成功返回数据以后的逻辑
                            $co2 = new SwooleCoClient();
                            $swoole2 = $co2->getInstance('swoole2');
                            $swoole2->call('TestService::test_node')
                                ->onSuccess(function ($response_data) {
                                    //$response_data 响应数据，请求的service返回的数据结构
                                    //在这里处理请求成功返回数据以后的逻辑
                                })->onFail(function ($msg, $code) {
                                    //请求失败了，在这里记录日志等
                                });
                            $swoole2->run();
                        })->onFail(function ($msg, $code) {
                            //请求失败了，在这里记录日志等
                        });
                    $swoole->run();
                } catch (\Throwable $throwable) {
                    //寻找服务等错误，在这里查看
                }

                //代码在这里返回，此次请求结束，但是上面的服务调用可能还在执行中!!!
                return ['code' => 1, 'msg' => $msg];
            }
        }
- 建议 & 注意
    - 默认是非守护进程方式启动， 推荐使用 `supervisor` 管理 `swoole` 进程
    - `v1.2.2` 后支持通过 `.env` 文件区分测试环境和正式环境监听的服务 `ip` 和 `port`
    - 改动配置或服务代码后必须 `reload` 或重启服务进程，不然不会生效
    - `php` `fpm` 和 `cli` 的 `php.ini` 通常不是一个文件，在 `fpm` 中使用时需要保证对应的 `php.ini` 文件中添加了 `swoole` 扩展
    - 异步协程客户端在非密集型 `cpu io` 的场景下可以提高性能，推荐使用，但是注意异步代码的执行逻辑和编写方式
    - 暂不支持传递图片文件等资源
- 迭代计划
    - 增加结合 `jackdou/management` 管理后台实现多机器服务下发配置 [了解详情](https://github.com/jhabc1314/management)（v1.2.1）
    - 支持基础 `web socket` 功能
    - 支持请求头，传递资源图片等功能
    - 支持单元测试和自动化测试
    - 服务节点检测，自动下线服务
    - 集成 `supervisor` 自动下发配置管理 [了解详情](http://www.jackdou.top)
