<?php
return [
    /*
     * web socket 配置
     */
    'socket' => [
        'host' => '0.0.0.0',
        'port' => 9501,
    ],
    /*
     * 具体的参数详细说明请参见官方文档 https://wiki.swoole.com/wiki/page/274.html
     */
    'server' => [
        'name' => 'swoole', //服务名称，启动成功后可以通过 ps aux | grep name 查看启动的进程
        'host' => '127.0.0.1', //监听的本机ip，如果需要局域网通信则需要监听局域网ip
        'port' => '8820',
        'serialize_type' => 1, //序列化类型 1 serialize 2 json
        'setting' => [
            'worker_num' => swoole_cpu_num() + 1, //work 进程数，内部采用协程，设置和cpu核数一致或多一个
            'max_request' => 1000, //同步无状态的server work 进程超过此最大请求数后会自动退出，释放内存
            'task_worker_num' => 1, //任务进程数，大于0即开启任务进程
            'task_max_request' => 1000, //设置task进程的最大任务数
            'dispatch_mode' => 3, //数据包分发模式，具体参见文档
            'daemonize' => false, //守护进程模式,关闭，使用supervisor管理比较合适
            'backlog' => 128, //同时可以保持的最大等待连接数
            'log_file' => !defined('TEST_SWOOLE_DEBUG') ? storage_path('log/swoole.log') : __DIR__ . '/../../swoole.log',
            'pid_file' => !defined('TEST_SWOOLE_DEBUG') ? storage_path('log/server.pid') : __DIR__ . '/../../server.pid',
            'enable_coroutine' => true, //默认使用协程
            'max_coroutine' => 3000, //默认3000
            'task_enable_coroutine' => true, //协程支持任务进程
        ],
    ],
];
