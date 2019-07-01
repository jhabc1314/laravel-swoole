<?php

namespace JackDou\Swoole\Services;

use Swoole\Server;

class SwooleServerService extends SwooleService
{
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

    /**
     * 热重启
     */
    public function reload()
    {
        posix_kill(file_get_contents(self::$config['setting']['pid_file']), SIGUSR1);
    }

    /**
     * 服务停止
     */
    public function stop()
    {
        posix_kill(file_get_contents(self::$config['setting']['pid_file']), SIGTERM);
    }

}