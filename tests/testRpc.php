<?php
/**
 *
 * User: jiangheng
 * Date: 2019/1/14
 * Time: 20:49
 */
namespace Jackdou\Swoole\Tests;
if (file_exists(__DIR__ . '../autoload.php')) {
    require __DIR__ . '../autoload.php';
} else {
    require __DIR__ . '/../vendor/autoload.php';
}
class testRpc
{
    public function __construct()
    {
        ini_set("display_errors", "on");
        //$this->run();
    }

    public function run()
    {
        $server = new \Jackdou\Swoole\Services\SwooleServerService();
        $server->registerServer();
    }
}

$a = new testRpc();
$a->run();
