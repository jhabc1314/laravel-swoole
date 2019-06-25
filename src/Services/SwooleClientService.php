<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-20
 * Time: 下午8:08
 */

namespace JackDou\Swoole\Services;

use JackDou\Swoole\Exceptions\SwooleRequestException;
use Swoole\Client;

class SwooleClientService extends SwooleService
{

    public $host;
    public $port;

    public function __construct()
    {
        parent::__construct();
    }

    public function getInstance($name = 'swoole')
    {
        $this->initClient()
            ->initSetting()
            ->selectServer($name);
        return $this;
    }


    /**
     * 调用服务
     * @param string $call
     * @param array $params
     *
     * @throws SwooleRequestException
     *
     * @return $this
     */
    public function call(string $call, array $params)
    {
        $this->connect()
            ->send($call, $params);
        return $this;
    }

    /**
     * 初始化客户端
     *
     * @return $this
     */
    public function initClient()
    {
        $this->client = new Client(SWOOLE_TCP);
        return $this;
    }

    /**
     * 初始化设置项
     *
     * @return $this
     */
    public function initSetting()
    {
        $this->client->set($this->defaultConfig);
        return $this;
    }

    /**
     * 选择调用的server,多台ip根据算法随机选择
     *
     * @param string $name
     *
     * @return $this
     */
    public function selectServer($name = 'swoole')
    {
        //TODO
        return $this;
    }

    /**
     * 连接server
     *
     * @return $this
     *
     * @throws SwooleRequestException
     *
     */
    public function connect()
    {
        if (!$this->client->connect($this->host, $this->port)) {
            throw new SwooleRequestException('client connect to server error');
        }
        return $this;
    }

    /**
     * 发送需要执行的请求数据
     *
     * @param string $call serviceClass::func
     * @param array $params 请求的参数
     *
     * @return $this
     *
     * @throws SwooleRequestException
     */
    public function send(string $call, array $params)
    {
        $request = SwooleRequestService::getRequest($call, $params);
        $data = SwooleRequestService::pack($request);
        $res = $this->client->send($data);
        if ($res === false) {
            throw new SwooleRequestException(socket_strerror($this->client->errCode));
        }
        return $this;
    }

    /**
     * 同步阻塞获取响应结果
     *
     * @param float $timeout
     *
     * @return mixed
     *
     * @throws SwooleRequestException
     *
     */
    public function getResult($timeout = 0.5)
    {
        $recv = $this->client->recv();
        $this->client->close();

        $response = SwooleRequestService::unpack($recv);
        return $response;
    }


}