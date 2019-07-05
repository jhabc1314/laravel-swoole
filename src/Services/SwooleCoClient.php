<?php
/**
 * 异步协程客户端
 *
 * 可以批量发起请求最后获得结果
 *
 * User: jackdou
 * Date: 19-7-4
 * Time: 上午11:32
 */

namespace JackDou\Swoole\Services;

use Illuminate\Support\Facades\Log;
use JackDou\Swoole\Exceptions\SwooleRequestException;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class SwooleCoClient extends SwooleClient
{

    /**
     * @var array
     */
    public static $servers;

    /**
     * @var Channel
     */
    public $chan;

    public $wait_response;

    private $unique_id = null;

    /**
     * 获得请求服务和节点
     *
     * @param $server_name
     * @param string|null $ip
     *
     * @return $this
     */
    public function getInstance($server_name = 'swoole', ?string $ip = null)
    {
        if (is_null($this->chan)) {
            //保存请求和响应数据
            $this->chan = new Channel(512);
        }
        $this->server_name = $server_name;
        $this->node_host = $ip;
        return $this;
    }

    /**
     * 发起服务调用
     *
     * @param string $call
     * @param mixed ...$params
     *
     * @return $this
     *
     * @throws SwooleRequestException
     *
     * @throws \JackDou\Swoole\Exceptions\NotFoundException
     */
    public function call(string $call, ...$params)
    {
        //生成一个唯一的请求串号
        $request_id = SwooleRequestService::unique($this->server_name);
        $response = new SwooleResponse($request_id);
        $this->unique_id = $request_id;
        //选择服务节点
        $server_node = $this->getServerNode();
        $node = $this->choiceNode($server_node);
        $response->setHost($node['ip']);
        $response->setPort($node['port']);
        //待发送数据
        $response->setWaitSend(SwooleRequestService::getRequest($call, $params));

        $this->wait_response[$request_id] = $response;
        unset($response);
        return $this;
    }

    /**
     * 设置请求成功时的回调
     *
     * @param \Closure $closure
     *
     * @return $this
     *
     * @throws SwooleRequestException
     */
    public function onSuccess(\Closure $closure)
    {
        if (is_null($this->unique_id)) {
            throw new SwooleRequestException("please transfer call func first");
        }
        if (is_null($this->wait_response[$this->unique_id])) {
            throw new SwooleRequestException("Can't find wait reponse data");
        }
        /**
         * @var SwooleResponse $response
         */
        $response = $this->wait_response[$this->unique_id];
        $response->setSuccess($closure);
        $this->wait_response[$this->unique_id] = $response;
        unset($response);
        return $this;
    }

    /**
     * 设置请求失败时的回调
     *
     * @param \Closure $closure
     *
     * @return $this
     *
     * @throws SwooleRequestException
     */
    public function onFail(\Closure $closure)
    {
        if (is_null($this->unique_id)) {
            throw new SwooleRequestException("please transfer call func first");
        }
        if (is_null($this->wait_response[$this->unique_id])) {
            throw new SwooleRequestException("Can't find wait response data");
        }
        /**
         * @var SwooleResponse $response
         */
        $response = $this->wait_response[$this->unique_id];
        $response->setFail($closure);
        $this->wait_response[$this->unique_id] = $response;
        unset($response);
        return $this;
    }

    /**
     * 发送数据，接收响应
     *
     * @param float $timeout
     *
     * @return int
     */
    public function run($timeout = 0.5)
    {
        //循环所有的待接收的响应
        $ms = microtime(true);
        $request_num = 0;
        /**
         * @var $response SwooleResponse
         */
        foreach ($this->wait_response as $request_id => $response) {
            $request_num++;
            //发起请求
            go(function () use ($request_id, $response, $timeout) {
                $response->co_client = new Coroutine\Client(SWOOLE_TCP);
                $response->co_client->set(SwooleRequestService::$pack_config);
                if (!$response->co_client->connect($response->getHost(), $response->getPort(), $timeout)) {
                    //连接失败，也压入chan
                    $response->setResponse(new SwooleRequestException("connect to {$this->server_info()} timeout", self::ERR_CONNECT_TIMEOUT));
                    $this->chan->push($response);
                    unset($this->wait_response[$request_id]);
                } else {
                    $data = SwooleRequestService::pack($response->getWaitSend());
                    $send = $response->co_client->send($data);
                    if ($send === false) {
                        $response->setResponse(new SwooleRequestException(socket_strerror($response->co_client->errCode), self::ERR_SEND));
                        $this->chan->push($response);
                        unset($this->wait_response[$request_id]);
                        return;
                    }
                    $recv = $response->co_client->recv($timeout);

                    if ($recv === "") {
                        //服务端主动关闭连接
                        $response->setResponse(new SwooleRequestException("{$this->server_info()} close the connect", self::ERR_SERVER_CLOSE));
                        $this->chan->push($response);
                        unset($this->wait_response[$request_id]);
                        return;
                    }
                    //接收数据失败
                    if ($recv === false) {
                        $response->setResponse(new SwooleRequestException("recv {$this->server_info()} error:" . $response->co_client->errCode, self::ERR_RECV));
                        $this->chan->push($response);
                        unset($this->wait_response[$request_id]);
                        return;
                    }
                    $response->setResponse(SwooleRequestService::unpack($recv));
                    $this->chan->push($response);
                    unset($this->wait_response[$request_id]);
                }
            });
        }
        //接收请求
        $receive = 0;
        while (true) {
            /**
             * @var $response SwooleResponse | bool
             */
            $response = $this->chan->pop($timeout);
            if ($response != false) {
                $receive++;
                $response->co_client->close();
                $this->hookCallback($response);
            }
            if ($receive >= $request_num) {
                break;
            }
            if (microtime(true) - $ms > $timeout) {
                break;
            }
        }
        return $receive;
    }

    public function __toString():string
    {
        return $this->unique_id;
    }

    /**
     * 回调指定方法
     *
     * @param SwooleResponse $response
     */
    public function hookCallback(SwooleResponse $response)
    {
        go(function () use ($response) {
            $data = $response->getResponse();
            if ($data instanceof \Exception) {
                //说明发生了错误
                Log::error($data->getMessage());
                if (is_callable($response->getFail())) {
                    call_user_func($response->getFail(), $data->getMessage(), $data->getCode());
                }
            } else {
                if (is_callable($response->getSuccess())) {
                    call_user_func($response->getSuccess(), $data);
                }
            }
        });
    }

}