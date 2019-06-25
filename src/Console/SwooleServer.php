<?php
namespace JackDou\Swoole\Console;

use Illuminate\Console\Command;
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
    protected $signature = 'swoole:server {cmd=start}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '注册 swoole tcp server
                             start:启动本地 server 服务
                             stop:停止本地 server 服务
                             reload:平滑重启所有工作进程';

    public function __construct(SwooleServerService $server)
    {
        parent::__construct();
        $this->server = $server;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmd = $this->argument('cmd');
        switch (strtolower($cmd)) {
            case 'start':
                $this->server->initServer()
                    ->initSetting()
                    ->initEvent(new SwooleEventService())
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