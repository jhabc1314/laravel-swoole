<?php
namespace Jackdou\Swoole\Services;

use \Swoole\WebSocket\Server;

class SwooleSocketService extends SwooleService
{
    public function __construct()
    {
        $this->initConfig();
        $this->registerServer();
    }

    private function initConfig()
    {
        $this->config = config('swoole.socket');
        $this->ip = $this->config['ip'];
        $this->port = $this->config['port'];
    }

    private function registerServer()
    {
        $this->server = new Server($this->ip, $this->port);

        $this->server->on('open', function (Server $server, $request) {
            echo "server: handshake success with fd{$request->fd}\n";
        });

        $this->server->on('message', function (Server $server, $frame) {
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });

        $this->server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });
    }


}