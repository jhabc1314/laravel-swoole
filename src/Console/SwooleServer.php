<?php
namespace Jackdou\Swoole\Console;

use Illuminate\Console\Command;
use Jackdou\Swoole\Services\SwooleServerService;

class SwooleServer extends Command
{
    public $server;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动一个 swoole server';

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
        $this->server->registerServer();
        $this->server->server->start();
    }
}