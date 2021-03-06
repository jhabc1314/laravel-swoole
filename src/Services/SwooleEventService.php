<?php
/**
 * EventService
 * User: jackdou
 * Date: 2019/6/23
 * Time: 13:17
 */

namespace JackDou\Swoole\Services;

use Illuminate\Support\Facades\Notification;
use JackDou\Management\Models\Notice;
use JackDou\Management\Models\Notify;
use JackDou\Management\Notifications\SwooleServerNotify;
use JackDou\Swoole\Facade\Service;
use JackDou\Swoole\Management\CrontabService;
use Swoole\Server;

class SwooleEventService extends SwooleService
{

    /**
     * SwooleEventService constructor.
     *
     * @param string $server_name
     *
     * @throws \JackDou\Swoole\Exceptions\NotFoundException
     *
     */
    public function __construct(string $server_name)
    {
        $this->initConfig($server_name);
    }

    /**
     * @var array
     */
    public $events = [
        'connect',
        'start',
        'shutdown',
        'workerStart',
        'workerStop',
        'receive',
        'close',
        'task',
        'finish',
        'managerStart',
        'managerStop',
    ];

    /**
     * Server启动在主进程的主线程回调此函数
     *
     * @param Server $server
     */
    public function onStart(Server $server)
    {
        swoole_set_process_name(self::$config['name'] . '_master');
        echo "onStart..." . $server->master_pid . "\n";
    }

    /**
     * Server 正常结束时发生 kill -15 才可以 -USER1 -USER2 一个reload，一个stop
     *
     * @param Server $server
     */
    public function onShutdown(Server $server)
    {
        echo "onShutdown..." . $server->master_pid . "\n";
    }

    /**
     * 在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     *
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStart(Server $server, $worker_id)
    {
        $p_type = $server->taskworker ? "task" : "worker";
        swoole_set_process_name(self::$config['name'] . $p_type);

        //cron 重启拉取默认
        if (self::$config['name'] == SwooleService::CRON_MANAGER && !empty(config('swoole.management_host'))) {
            $management_host = config('swoole.management_host');
            $crontabs = Service::getInstance(SwooleService::NODE_MANAGER, $management_host)
                ->call('CrontabService::crontabs', $this->host)
                ->getResult(1);
            if (isset($crontabs['code']) && $crontabs['code'] === 0) {
                $crontabs = $crontabs['data'];
                foreach ($crontabs as $crontab) {
                    $res = CrontabService::start($crontab);
                    if ($res['code'] == 0) {
                        $crontab = $res['data'];
                        Service::getInstance(SwooleService::NODE_MANAGER, $management_host)
                            ->call('CrontabService::save', $crontab)
                            ->getResult();
                        //发送全局通知
                        if (class_exists(Notify::class)) {
                            event(new \JackDou\Management\Events\Notify([
                                'notice_title' => '调度任务服务重启',
                                'notice_text' => '拉取' . $crontab->id . '任务成功',
                                'notice_level' => Notify::NOTICE,
                                'notice_url' => route('crontab.index')
                            ]));
                        }
                    }
                }
            }
        }
        echo "onWorkerStart,this is a $p_type..." . $worker_id . "\n";
    }

    /**
     * 在worker进程终止时发生。在此函数中可以回收worker进程申请的各类资源
     *
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerStop(Server $server, $worker_id)
    {
        $p_type = $server->taskworker ? "task" : "work";

        echo "onWorkerStop,this is a $p_type..." . $worker_id . "\n";
    }

    /**
     * 仅在开启reload_async特性后有效。异步重启特性，会先创建新的Worker进程处理新请求，旧的Worker进程自行退出
     *
     * @param Server $server
     * @param $worker_id
     */
    public function onWorkerExit(Server $server, $worker_id)
    {
        $p_type = $server->taskworker ? "task" : "work";

        echo "onWorkerExit,this is a $p_type..." . $worker_id . "\n";
    }

    /**
     * 有新的连接进入时，在worker进程中回调
     * 当设置dispatch_mode = 1/3时会自动去掉onConnect/onClose事件回调
     *
     * @param Server $server
     * @param $fd
     * @param $reactor_id
     */
    public function onConnect(Server $server, $fd, $reactor_id)
    {
        $p_type = $server->taskworker ? "task" : "work";

        echo "onConnect, this is a $p_type" . "\n";
    }

    /**
     * 接收到数据时回调此函数，发生在worker进程中
     *
     * @param Server $server
     * @param $fd
     * @param $reactor_id
     * @param $data
     *
     */
    public function onReceive(Server $server, $fd, $reactor_id, $data)
    {
        echo "onReceive...\n";
        //处理请求。。。
        try {
            $receive = SwooleRequestService::unpack($data, self::$config['serialize_type']);

            $result = SwooleRequestService::call($receive, self::$config['namespace']);
            $response = SwooleRequestService::pack($result, self::$config['serialize_type']);
            $server->send($fd, $response);
        } catch (\Exception $exception) {
            //出现错误了，发送错误默认数据
            $server->send($fd, SwooleRequestService::errorResponse($exception->getMessage()));
            $server->close($fd);
        }
    }

    /**
     * TCP客户端连接关闭后，在worker进程中回调此函数
     *
     * @param Server $server
     * @param $fd
     * @param $reactor_id
     */
    public function onClose(Server $server, $fd, $reactor_id)
    {
        echo "onClose..." . "\n";
    }

    /**
     * 在task_worker进程内被调用。worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务
     *
     * @param Server $server
     * @param int $task_id
     * @param int $src_worker_id
     * @param mixed $data
     */
    public function onTask(Server $server, int $task_id, int $src_worker_id, $data)
    {
        echo "onTask..." . "\n";
    }

    /**
     * 当worker进程投递的任务在task_worker中完成时，
     * task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程
     *
     * @param Server $server
     * @param int $task_id
     * @param string $data
     */
    public function onFinish(Server $server, int $task_id, string $data)
    {
        echo "onFinish..." . "\n";
    }

    /**
     * 当Worker/Task进程发生异常后会在Manager进程内回调此函数
     *
     * @param Server $server
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @param int $signal
     */
    public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
    {
        echo "onWorkerError..." . "\n";
    }

    /**
     * 当管理进程启动时调用它
     *
     * @param Server $server
     */
    public function onManagerStart(Server $server)
    {
        swoole_set_process_name(self::$config['name'] . '_manager');
        echo "onManagerStart..." . "\n";
    }

    /**
     * 当管理进程结束时调用它
     * onManagerStop触发时，说明Task和Worker进程已结束运行，已被Manager进程回收
     *
     * @param Server $server
     */
    public function onManagerStop(Server $server)
    {
        echo "onManagerStop..." . "\n";
    }
}