<?php
/**
 * 同步客户端
 *
 * User: jackdou
 * Date: 19-6-20
 * Time: 下午8:08
 */

namespace JackDou\Swoole\Services;

use Illuminate\Support\Facades\Log;
use JackDou\Swoole\Exceptions\NotFoundException;
use JackDou\Swoole\Exceptions\SwooleRequestException;
use Swoole\Client;

class SwooleClientService extends SwooleClient
{

    /**
     * 复用服务连接
     *
     * @var array
     */
    public static $service;


    /**
     * @var array 待发送的数据
     */
    public $wait_send;

    /**
     * 指定要请求的服务名称
     *
     * @param string $name
     * @param string $ip 指定连接服务的哪个ip节点
     *
     * @return $this
     *
     * @throws \JackDou\Swoole\Exceptions\NotFoundException
     */
    public function getInstance(string $name = 'swoole', ?string $ip = null)
    {
        if (empty($name)) {
            throw new NotFoundException("server name is empty");
        }
        $this->server_name = $name;
        $this->node_host = $ip;
        return $this;
    }

    /**
     * 调用服务
     *
     * @param string $call
     * @param array $params
     *
     * @return $this
     */
    public function call(string $call, ...$params)
    {
        $this->wait_send = SwooleRequestService::getRequest($call, $params);
        return $this;
    }

    /**
     * 初始化客户端
     *
     * @param $timeout float
     *
     * @throws SwooleRequestException
     *
     * @return Client
     */
    public function initClient($timeout = 0.1) :Client
    {
        $client = new Client(SWOOLE_TCP);
        $client->set(SwooleRequestService::$pack_config);
        if (!$client->connect($this->host, $this->port, $timeout)) {
            throw new SwooleRequestException("connect {$this->server_info()} timeout", self::ERR_CONNECT_TIMEOUT);
        }
        return self::$service[$this->server_name] = $client;
    }

    /**
     * 选择调用的server,多台ip根据算法随机选择
     * 可以继承此类自定义查找节点的方式
     *
     * @param float $timeout
     *
     * @throws NotFoundException
     * @throws SwooleRequestException
     *
     * @return Client
     */
    public function selectServer($timeout = 0.5) :Client
    {
        //如果有已经存在的连接并且可用就直接复用
        if (isset(self::$service[$this->server_name]) && self::$service[$this->server_name]->isConnected()) {
            //判断连接的节点是否是指定的节点
            if (!empty($this->node_host)) {
                $sock_name_arr = self::$service[$this->server_name]->getsockname();
                if ($sock_name_arr['host'] == $this->node_host) {
                    return self::$service[$this->server_name];
                }
            } else {
                return self::$service[$this->server_name];
            }
        }
        unset(self::$service[$this->server_name]);
        $server_node = $this->getServerNode();

        $this->choiceNode($server_node);
        return $this->initClient($timeout);
    }



    /**
     * 发送需要执行的请求数据
     *
     * @param Client $client
     *
     * @throws SwooleRequestException
     */
    public function send(Client $client)
    {
        $data = SwooleRequestService::pack($this->wait_send);
        $res = $client->send($data);
        if ($res === false) {
            throw new SwooleRequestException(socket_strerror($client->errCode), self::ERR_SEND);
        }
    }

    /**
     * 同步阻塞获取响应结果
     *
     * @param float $timeout
     *
     * @throws \Throwable
     *
     * @return mixed
     */
    public function getResult($timeout = 0.5)
    {
        try {
            //寻找服务节点
            $client = $this->selectServer($timeout);
            //发送数据
            $this->send($client);
            //同步接收响应
            $recv = $client->recv();
            if ($recv === "") {
                //服务端主动关闭连接
                throw new SwooleRequestException("{$this->server_info()} close the connect", self::ERR_SERVER_CLOSE);
            }
            //接收数据失败
            if ($recv === false) {
                throw new SwooleRequestException("recv {$this->server_info()} data error:" . $client->errCode, self::ERR_RECV);
            }
            $response = SwooleRequestService::unpack($recv);
        } catch (\Throwable $e) {
            isset($client) and $client->close();
            //失败以后删除保存的数据
            if (isset(self::$service[$this->server_name])) {
                unset(self::$service[$this->server_name]);
            }
            Log::error("SwooleClientService:" . __FUNCTION__ . ":line:" . $e->getLine() . $e->getMessage() . $e->getCode());
            if ($e instanceof SwooleRequestException) {
                return false;
            }
            return null;
        }
        return $response;
    }

    /**
     * 节点机器存活检测
     * Service::getInstance()->ping()->getResult();
     */
    public function ping()
    {
        $this->wait_send = SwooleRequestService::getRequest("ping");
        return $this;
    }

    public function __destruct()
    {
        self::$service = null;
        // TODO: Implement __destruct() method.
        unset($this->server_name, $this->host, $this->port, $this->wait_send);
    }
}