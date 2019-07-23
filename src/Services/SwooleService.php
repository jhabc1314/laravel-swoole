<?php
namespace JackDou\Swoole\Services;

use JackDou\Swoole\Exceptions\NotFoundException;
use Swoole\Server;

class SwooleService
{
    /**
     * @version 1.2
     */
    public const VERSION = "1.2";

    /**
     * @var Server
     */
    public $server;

    const NODE_MANAGER = 'node_manager';

    const CRON_MANAGER = 'cron_manager';

    protected $server_name;

    protected static $config;

    protected $host;

    protected $port;

    /**
     * 初始化服务配置
     *
     * @param $server_name string
     *
     * @throws NotFoundException
     *
     */
    public function initConfig(string $server_name)
    {
        $this->server_name = $server_name;
        if (in_array($this->server_name, config('swoole.kernel_servers'))) {
            self::$config = config('swoole.' . $this->server_name);
        } else {
            self::$config = config('swoole.server');
            if (self::$config['name'] != $server_name) {
                throw new NotFoundException("$server_name server don't exist");
            }
        }
        $this->host = self::$config['host'];
        $this->port = self::$config['port'];
        $setting = array_merge(config('swoole.servers_setting'), self::$config['setting']);
        self::$config['setting'] = array_merge($setting, SwooleRequestService::$pack_config);
    }
}
