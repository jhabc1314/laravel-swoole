<?php
namespace Jackdou\Swoole\Services;

use Swoole\Http\Request;
use Swoole\Http\Response;
use \Swoole\WebSocket\Server;

class SwooleSocketService extends SwooleService
{
    public function __construct()
    {
        $this->initConfig();
    }

    private function initConfig()
    {
        $this->config = config('swoole.socket');
        $this->ip = $this->config['ip'];
        $this->port = $this->config['port'];
    }

    public function registerServer()
    {
        $this->server = new Server($this->ip, $this->port);
        //var_dump($this->server->master_pid);

        $this->server->on('open', [$this, 'onOpen']);

        $this->server->on('message', [$this, 'onMessage']);

        $this->server->on('request', [$this, 'onRequest']);

        $this->server->on('task', [$this, 'onTask']);

        $this->server->on('close', [$this, 'onClose']);
    }

    /**
     * 握手成功的回调
     * @param Server $server
     * @param $request
     */
    public function onOpen(Server $server, $request)
    {
        echo "master pid is {$server->master_pid}\n";
        echo "server: handshake success with fd{$request->fd}\n";

    }

    /**
     * 服务器接收到消息的回调
     * @param Server $server
     * @param $frame
     */
    public function onMessage(Server $server, $frame)
    {
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";

        $start_fd = 0;
        while(true)
        {
            $conn_list = $server->getClientList($start_fd, 10);
            if ($conn_list===false or count($conn_list) === 0)
            {
                echo "finish\n";
                break;
            }
            $start_fd = end($conn_list);
            var_dump($conn_list);
            foreach($conn_list as $fd)
            {
                $server->push($fd, $frame->data);
            }
        }

        //$server->push($frame->fd, $frame->data);
    }

    /**
     * 接收到http请求后的回调
     * @param Request $request
     * @param Response $response
     */
    public function onRequest(Request $request, Response $response)
    {
        $response->end(<<<HTML
    <h1>Swoole WebSocket Server</h1>
    <script>
var wsServer = 'ws://127.0.0.1:9501';
var websocket = new WebSocket(wsServer);
websocket.onopen = function (evt) {
	console.log("Connected to WebSocket server.");
};

websocket.onclose = function (evt) {
	console.log("Disconnected");
};

websocket.onmessage = function (evt) {
	console.log('Retrieved data from server: ' + evt.data);
};

websocket.onerror = function (evt, e) {
	console.log('Error occured: ' + evt.data);
};
</script>
HTML
        );
    }

    /**
     * 执行任务
     * @param Server $server
     * @param $worker_id
     * @param $task_id
     * @param $data
     * @return string
     */
    public function onTask(Server $server, $worker_id, $task_id, $data)
    {
        var_dump($worker_id, $task_id, $data);
        return "hello world\n";
    }

    /**
     * 客户端关闭的回调
     * @param Server $server
     * @param $fd
     */
    public function onClose(Server $server, $fd)
    {
        echo "client {$fd} closed\n";
    }


}