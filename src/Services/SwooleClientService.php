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
use Illuminate\Support\Facades\Storage;
use JackDou\Swoole\Exceptions\NotFoundException;
use JackDou\Swoole\Exceptions\SwooleRequestException;
use Swoole\Client;

class SwooleClientService
{

    /**
     * 接收响应数据失败
     */
    const ERR_RECV = 1003;

    /**
     * 服务端主动关闭连接
     */
    const ERR_SERVER_CLOSE = 1004;

    /**
     * 连接超时，服务可能不可用或者连接满了
     */
    const ERR_CONNECT_TIMEOUT = 1005;

    /**
     * 发送请求数据失败
     */
    const ERR_SEND = 1006;

    /**
     * 复用服务连接
     *
     * @var array
     */
    public static $service;

    /**
     * @var string
     */
    public $server_name;

    /**
     * @var array 待发送的数据
     */
    public $wait_send;

    public $host;
    public $port;
    public $node_host;

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

        //根据不同配置选择节点
        if ($this->server_name == SwooleService::NODE_MANAGER) {
            $node_find_conf = config('swoole.' . SwooleService::NODE_MANAGER);
        } else {
            $node_find_conf = config('swoole.server');
        }
        if ($node_find_conf['node_find_type'] == 1) {
            $server_node = config("server_node.{$this->server_name}");
        } else {
            $file_path = config('swoole.node_conf_path') . $this->server_name . '.conf';
            $server_node = json_decode(file_get_contents($file_path), true);
        }
        if (empty($server_node)) {
            throw new NotFoundException("cant find {$this->server_name} server node config");
        }

        $this->choiceNode($server_node);
        return $this->initClient($timeout);
    }

    /**
     * 从给到的节点列表中选择一个
     *
     * @param array $server_node
     *
     * @throws NotFoundException
     * @throws SwooleRequestException
     *
     * @return bool|string
     *
     */
    public function choiceNode(array $server_node)
    {
        $online = [];
        $weight = 0;
        //如果指定了节点就只查看指定节点状态
        $target_node = null;
        foreach ($server_node as $node) {
            if ($node['status'] == 'online') {
                $node['weight_range'] = [$weight, ($weight += $node['weight'])];
                $online[] = $node;
                if (!empty($this->node_host) && $node['ip'] == $this->node_host) {
                    $target_node = $node;
                    break;//找到直接结束
                }
            }
        }

        if (!empty($this->node_host) && is_null($target_node)) {
            throw new NotFoundException("can not find {$this->node_host} node");
        }

        if (empty($online)) {
            throw new NotFoundException("can not find {$this->server_name} online node");
        }
        if ($weight < 0) {
            throw new SwooleRequestException("{$this->server_name} node weight too small, it must >= 0 at least");
        }
        //0-$weight rand a number
        $choice = mt_rand(0, $weight);
        foreach ($online as $node) {
            if ($choice >= $node['weight_range'][0] && $choice <= $node['weight_range'][1]) {
                $this->host = $node['ip'];
                $this->port = $node['port'];
                return true;
            }
        }
        return false;
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
            if ($e instanceof SwooleRequestService) {
                return false;
            }
            throw $e;
        }
        return $response;
    }

    public function server_info()
    {
        return "server {$this->server_name}:{$this->host} ";
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