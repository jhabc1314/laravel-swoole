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
     * @return string
     */
    public function send($param)
    {
        $this->client = new Client(SWOOLE_TCP);
        $this->client->set([
            'open_eof_check' => true,
            'package_eof' => "\r\n",
            'open_length_check' => true, //开启包长检测
            'package_length_type' => 'N', //长度类型
            'package_body_offset' => 4, //包体偏移量
            'package_length_offset' => 0, //协议中的包体长度字段在第几字节
        ]);
        if (!$r = $this->client->connect('127.0.0.1', 8821)) {
            exit('connect fail');
        }
        //var_dump($r);
        //$this->client->close();die;
        $param .= "\r\n";
        $data = pack('N', strlen($param)) . $param;
        //var_dump($data);
        $this->client->send($data);
        $recv = $this->client->recv();
        $this->client->close();
        return substr($recv, 4);
    }


}