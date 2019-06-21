<?php
namespace Jackdou\Swoole\Services;

use Swoole\Client;
use Swoole\Server;

class SwooleService
{
    /**
     * @var Server
     */
    public $server;

    /**
     * @var Client
     */
    public $client;

    public $config;

    public $host;

    public $port;

    public function __construct()
    {
        $this->config = config('swoole.server');
        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
    }

}
