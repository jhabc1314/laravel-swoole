<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-7-4
 * Time: 下午1:14
 */

namespace JackDou\Swoole\Services;

use JackDou\Swoole\Exceptions\NotFoundException;
use JackDou\Swoole\Exceptions\SwooleRequestException;

class SwooleClient
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
     * 服务名
     *
     * @var string
     */
    protected $server_name;

    protected $host;
    protected $port;
    protected $node_host;


    /**
     * 获取服务节点列表
     *
     * @return \Illuminate\Config\Repository|mixed
     *
     * @throws NotFoundException
     */
    public function getServerNode()
    {
        //根据不同配置选择节点
        if (in_array($this->server_name, config('swoole.kernel_servers'))) {
            $node_find_conf = config('swoole.' . $this->server_name);
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
        return $server_node;
    }

    /**
     * 从给到的节点列表中选择一个
     *
     * @param array $server_node
     *
     * @throws NotFoundException
     * @throws SwooleRequestException
     *
     * @return array
     */
    public function choiceNode(array $server_node)
    {
        $online = [];
        $weight = 0;
        $target_node = null;
        foreach ($server_node as $node) {
            if (!empty($this->node_host) && $node['ip'] == $this->node_host) {
                //如果指定了节点就只查看指定节点
                $this->host = $node['ip'];
                $this->port = $node['port'];
                return $node; //找到直接结束
            }
            if ($node['status'] == 'online') {
                $node['weight_range'] = [$weight, ($weight += $node['weight'])];
                $online[] = $node;
            }
        }

        if (!empty($this->node_host) && is_null($target_node)) {
            throw new NotFoundException("can not find {$this->node_host} target node");
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
                return $node;
            }
        }
        throw new NotFoundException("can't find {$this->server_name} node");
    }


    public function server_info()
    {
        return "server {$this->server_name}:{$this->host} ";
    }
}