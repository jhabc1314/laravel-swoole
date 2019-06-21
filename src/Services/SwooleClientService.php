<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-20
 * Time: 下午8:08
 */

namespace JackDou\Swoole\Services;

use Swoole\Client;

class SwooleClientService extends SwooleService
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 发送需要执行的请求数据
     *
     * callback  => [service::class, function],
     * params => []
     * @param array $param
     *
     * @return array
     */
    public function send($param)
    {
        $this->client = new Client(SWOOLE_SOCK_TCP);
        if (!$this->client->connect('127.0.0.1', 8821)) {
            exit('connect fail');
        }
        $this->client->send(json_encode($param));
        $recv = $this->client->recv();
        $this->client->close();
        return json_decode($recv);
    }


}