#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-21
 * Time: 上午9:54
 */


if (file_exists(__DIR__ . '/autoload.php')) {
    require __DIR__ . '/autoload.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}


if ($argv[1] == 'server') {
    $server = new \Jackdou\Swoole\Tests\testRpc();
    $server->run();
} else {
    $client = new \JackDou\Swoole\Tests\testClient();
    $client->run('hello world');
}

