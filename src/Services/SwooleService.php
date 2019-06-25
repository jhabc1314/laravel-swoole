<?php
namespace JackDou\Swoole\Services;

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

    public static $config;

    public $host;

    public $port;

    public $defaultConfig;

    public function __construct()
    {
        $config = !defined('TEST_SWOOLE_DEBUG') ? config('swoole') : require (__DIR__ . '/../config/swoole.php');
        self::$config = $config['server'];
        $this->host = self::$config['host'];
        $this->port = self::$config['port'];
        $this->defaultSetting();
        array_merge(self::$config['setting'], $this->defaultConfig);
    }

    public function defaultSetting()
    {
        $this->defaultConfig = [
            'open_eof_check' => 1,
            'package_eof' => "\r\n",
            'open_length_check' => true, //开启包长检测
            'package_length_type' => 'N', //长度类型
            'package_body_offset' => 4, //包体偏移量
            'package_length_offset' => 0, //协议中的包体长度字段在第几字节
        ];
    }

}
