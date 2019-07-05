<?php
/**
 * co 客户端响应类
 *
 * User: jackdou
 * Date: 19-7-4
 * Time: 下午5:17
 */

namespace JackDou\Swoole\Services;

use Swoole\Coroutine\Client;

class SwooleResponse
{

    protected $onSuccess;
    protected $onFail;
    
    public $request_id;

    /**
     * @var Client
     */
    public $co_client;

    protected $response = null;

    protected $host;
    protected $port;

    protected $wait_send;

    public function __construct($request_id)
    {
        $this->request_id = $request_id;
    }

    public function setResponse($data)
    {
        $this->response = $data;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setSuccess(\Closure $closure)
    {
        $this->onSuccess = $closure;
    }

    public function setFail(\Closure $closure):void
    {
       $this->onFail = $closure;
    }

    public function getSuccess():\Closure
    {
        return $this->onSuccess;
    }

    public function getFail():\Closure
    {
        return $this->onFail;
    }

    public function setHost(string $host):void
    {
        $this->host = $host;
    }

    public function setPort($port):void
    {
        $this->port = $port;
    }

    public function getHost():string
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function setWaitSend($wait_send):void
    {
        $this->wait_send = $wait_send;
    }

    public function getWaitSend()
    {
        return $this->wait_send;
    }
}