<?php

namespace Jackdou\Swoole;

use Illuminate\Support\ServiceProvider;

class SwooleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__ . '/config/swoole.php' => config_path('swoole.php'),
        ]);
    }

    public function register()
    {
        //合并用户自定义配置和默认配置
        $this->mergeConfigFrom(
            __DIR__ . '/config/swoole.php',
            'swoole'
        );

        //注册一个服务
        $this->app->singleton('swoole:server', 'Jackdou\Swoole\Console\SwooleServer');

        //注册web socket 服务
        $this->app->singleton('swoole:socket', 'Jackdou\Swoole\Console\SwooleSocket');

        //注册服务命令
        $this->commands([
            'swoole:server',
            'swoole:socket',
        ]);
    }
}