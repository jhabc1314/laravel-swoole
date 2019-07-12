<?php
namespace JackDou\Swoole\Console;

use Illuminate\Console\Command;
use JackDou\Swoole\Exceptions\NotFoundException;
use JackDou\Swoole\Services\SwooleEventService;
use JackDou\Swoole\Services\SwooleServerService;

class SwooleServer extends Command
{
    public $server;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:server {name=swoole} {cmd=start}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '注册 swoole tcp server
                             server_name start:启动本地 server_name 服务
                             server_name stop:停止本地 server_name 服务
                             server_name reload:重加载 server_name 工作进程';

    public function __construct(SwooleServerService $server)
    {
        parent::__construct();
        $this->server = $server;
    }

    /**
     * Execute the console command.
     *
     * @throws NotFoundException
     * @return mixed
     */
    public function handle()
    {
        $this->server->initConfig($this->argument('name'));
        $cmd = $this->argument('cmd');
        switch (strtolower($cmd)) {
            case 'start':
                $this->server->initServer()
                    ->initSetting()
                    ->initEvent(new SwooleEventService($this->argument('name')))
                    ->boot();
                break;
            case 'stop':
                $this->server->stop();
                break;
            case 'reload':
                $this->server->reload();
                break;
        }
        return true;
    }
}