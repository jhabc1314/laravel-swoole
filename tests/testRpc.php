<?php
/**
 *
 * User: jiangheng
 * Date: 2019/1/14
 * Time: 20:49
 */
namespace JackDou\Swoole\Tests;


class testRpc extends Test
{
    public function __construct()
    {
        ini_set("display_errors", "on");
        //$this->run();
    }

    public function run()
    {
        $server = new \JackDou\Swoole\Services\SwooleServerService();
        $server->initServer()
            ->initSetting()
            ->initEvent(new testEvent())
            ->boot();
    }
}
