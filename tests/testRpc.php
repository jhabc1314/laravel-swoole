<?php
/**
 *
 * User: jiangheng
 * Date: 2019/1/14
 * Time: 20:49
 */

class testRpc
{
    public function __construct()
    {
        ini_set("display_errors", "on");
        $this->run();
    }

    public function run()
    {
        $server = new \Jackdou\Swoole\Services\SwooleServerService();
        $server->registerServer();
    }
}
