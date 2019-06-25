<?php

namespace JackDou\Swoole\Services;

use Illuminate\Support\Facades\Storage;
use Swoole\Server;

class SwooleServerService extends SwooleService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取 TCP server 实例
     */
    public function initServer()
    {
        $this->server = new Server($this->host, $this->port);
        return $this;
    }

    public function initSetting()
    {
        $setting = array_filter(self::$config['setting'], function ($item) {
            return $item !== null;
        });

        $this->server->set($setting);
        return $this;
    }

    public function initEvent(SwooleEventService $eventService)
    {
        foreach ($eventService->events as $event) {
            $this->server->on($event, [$eventService, "on" . ucfirst($event)]);
        }
        return $this;
    }

    /**
     * 启动server服务
     */
    public function boot()
    {
        $this->server->start();
    }


    public function reload()
    {
        posix_kill(Storage::get('log/server.pid'), SIGUSR1);
    }

    public function stop()
    {
        posix_kill(Storage::get('log/server.pid'), SIGTERM);
    }



}