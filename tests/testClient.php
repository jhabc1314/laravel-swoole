<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-21
 * Time: 上午9:31
 */

namespace JackDou\Swoole\Tests;

use JackDou\Swoole\Services\SwooleClientService;
use JackDou\Swoole\Services\SwooleRequestService;

class testClient extends Test
{

    public function run($data)
    {
        $client = new SwooleClientService();
        $data = SwooleRequestService::getRequest([
            testApp::class,
            'func1'
        ], [$data, 'hehe!']);
        return $client->send($data);
    }
}