<?php
namespace JackDou\Swoole\Services;

use Swoole\Server;

class SwooleService
{
    public const VERSION = 1.1;

    /**
     * @var Server
     */
    public $server;

    public static $config;

    public $host;

    public $port;

    public $defaultConfig = [
        'open_eof_check' => 1,
        'package_eof' => "\r\n",
        'open_length_check' => true, //开启包长检测
        'package_length_type' => 'N', //长度类型
        'package_body_offset' => 4, //包体偏移量
        'package_length_offset' => 0, //协议中的包体长度字段在第几字节
    ];

    public function __construct()
    {
        self::$config = config('swoole.server');
        $this->host = self::$config['host'];
        $this->port = self::$config['port'];
        self::$config['setting'] = array_merge(self::$config['setting'], $this->defaultConfig);
    }
}
