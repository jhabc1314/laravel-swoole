<?php
namespace Jackdou\Swoole\Console;

use Illuminate\Console\Command;
use Jackdou\Swoole\Services\SwooleSocketService;

class SwooleSocket extends Command
{
    public $socket;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:socket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动一个 swoole webSocket';

    public function __construct(SwooleSocketService $socket)
    {
        parent::__construct();
        $this->socket = $socket;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->socket->server->start();
    }
}