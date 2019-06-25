<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-21
 * Time: 上午9:54
 */

define('TEST_SWOOLE_DEBUG', 1);
defined('TEST_SWOOLE_DEBUG') and
ini_set("error_log", __DIR__ . '/../../php_errors.log');

if (file_exists(__DIR__ . '/autoload.php')) {
    require __DIR__ . '/autoload.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}


if ($argv[1] == 'server') {
    $server = new \JackDou\Swoole\Tests\testRpc();
    $server->run();
} else {
    $client = new \JackDou\Swoole\Tests\testClient();
    $res = $client->run($argv[2] ?: 'hello world');
    print_r($res);
}

