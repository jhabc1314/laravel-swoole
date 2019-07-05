<?php

namespace JackDou\Swoole;

use Illuminate\Support\ServiceProvider;
use JackDou\Swoole\Rpc\RpcClient;
use JackDou\Swoole\Services\SwooleClientService;
use JackDou\Swoole\Services\SwooleCoClient;

class SwooleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__ . '/config/swoole.php' => config_path('swoole.php'),
            __DIR__ . '/config/server_node.php' => config_path('server_node.php'),
        ]);
    }

    public function register()
    {
        //合并用户自定义配置和默认配置
        $this->mergeConfigFrom(
            __DIR__ . '/config/swoole.php',
            'swoole'
        );
        //合并服务节点配置
        $this->mergeConfigFrom(
            __DIR__ . '/config/server_node.php',
            'server_node'
        );

        //注册一个服务
        $this->app->singleton('swoole:server', 'JackDou\Swoole\Console\SwooleServer');

        //注册web socket 服务
        $this->app->singleton('swoole:socket', 'JackDou\Swoole\Console\SwooleSocket');

        //注册服务命令
        $this->commands([
            'swoole:server',
            'swoole:socket',
        ]);

        //注册 facade
        $this->app->bind('service', function () {
            return new SwooleClientService();
        });
    }

    public function provides()
    {
        return ['swoole'];
    }
}