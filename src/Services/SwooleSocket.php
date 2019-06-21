<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-20
 * Time: 下午8:11
 */

namespace JackDou\Services;

class SwooleSocket
{
    public $config;

    public $ip;

    public $port;

    public function __construct()
    {
        $this->config = config('swoole.socket');
        $this->ip = $this->config['ip'];
        $this->port = $this->config['port'];
    }

}