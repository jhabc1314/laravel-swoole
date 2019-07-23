<?php
return [

    /*
    |--------------------------------------------------------------------------
    | laravel-swoole 微服务化 服务配置
    |--------------------------------------------------------------------------
    |
    | 配置规则为唯一服务名和具体配置内容
    |
    */
    'server' => [
        'name' => 'swoole', //服务名称，启动成功后可以通过 ps aux | grep your server name 查看启动的进程
        'host' => env('SERVER_HOST', '127.0.0.1'), //监听的本机局域网ip
        'port' => env('SERVER_PORT', 8820),
        'serialize_type' => 1, //序列化类型 1 serialize 2 json
        'namespace' => "App\\Services\\",//服务对应业务代码所在命名空间

        /*
        |--------------------------------------------------------------------------
        | 服务发现方式
        |--------------------------------------------------------------------------
        | 1 手动，使用 server_node.php 文件内的节点配置信息
        | 2 统一模式，使用 jackdou/management 项目的后台下发管理 需要配合 jackdou/management 项目
        |
         */
        'node_find_type' => 1,

        'setting' => [
            'log_file' => storage_path('logs/swoole.log'),
            'pid_file' => storage_path('logs/swoole.pid'),
            'task_worker_num' => 1, //任务进程数，大于0即开启任务进程
            'task_max_request' => 1000, //设置task进程的最大任务数
            'task_enable_coroutine' => true, //协程支持任务进程
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | 节点管理服务配置
    |--------------------------------------------------------------------------
    | 需要配合 jackdou/management 项目使用
    |
    */
    'node_manager' => [
        'name' => 'node_manager', //请勿更改
        'host' => env('SERVER_HOST', '127.0.0.1'), //监听本机局域网ip
        'port' => env('SERVER_NODE_PORT', 8821),
        'serialize_type' => 1, //序列化类型 1 serialize 2 json
        'namespace' => "JackDou\\Swoole\\Management\\",//服务对应业务代码所在命名空间
        'node_find_type' => 2,
        'setting' => [
            'worker_num' => 2,
            'task_worker_num' => 0, //任务进程数，大于0即开启任务进程
            'backlog' => 128, //同时可以保持的最大等待连接数
            'log_file' => storage_path('logs/node_manage.log'),
            'pid_file' => storage_path('logs/node_manage.pid'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 任务调度（定时任务服务）配置
    |--------------------------------------------------------------------------
    | 需要配合 jackdou/management 项目使用
    |
    */
    'cron_manager' => [
        'name' => 'cron_manager', //请勿更改
        'host' => env('SERVER_HOST', '127.0.0.1'), //监听本机局域网ip
        'port' => env('SERVER_CRON_PORT', 8822),
        'serialize_type' => 1, //序列化类型 1 serialize 2 json
        'namespace' => "JackDou\\Swoole\\Management\\",//服务对应业务代码所在命名空间
        'node_find_type' => 2,
        'running_log_path' => storage_path('logs/crontab/'), //运行日志记录文件
        'setting' => [
            'worker_num' => 1,
            'task_worker_num' => 0, //任务进程数，大于0即开启任务进程
            'max_request' => 0,
            'backlog' => 64, //同时可以保持的最大等待连接数
            'log_file' => storage_path('logs/cron_manager.log'),
            'pid_file' => storage_path('logs/cron_manager.pid'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 服务管理平台安装所在的机器 ip
    |--------------------------------------------------------------------------
    | 用于定时服务进程重启后拉取调度任务等
    |
    */
    'management_host' => env('MANAGEMENT_HOST', null),

    /*
    |--------------------------------------------------------------------------
    | 服务公用的配置选项
    |--------------------------------------------------------------------------
    | 如果需要不同的配置内容在上面的具体服务内配置
    | 优先级 服务单独配置 > 公共服务配置
    */
    'servers_setting' => [
        'worker_num' => swoole_cpu_num() + 1, //work 进程数，内部采用协程，设置和cpu核数一致或多一个
        'max_request' => 3000, //同步无状态的server work 进程超过此最大请求数后会自动退出重启，防止内存泄露
        'dispatch_mode' => 3, //数据包分发模式，具体参见文档
        'daemonize' => false, //守护进程模式,关闭，使用supervisor管理比较合适
        'backlog' => 128, //同时可以保持的最大等待连接数
        'enable_coroutine' => true, //默认使用协程
        'max_coroutine' => 3000, //默认3000
    ],

    /*
    |--------------------------------------------------------------------------
    | 服务下发文件默认保存位置
    |--------------------------------------------------------------------------
    |
    | 已经配置服务后请谨慎修改，修改后所有服务都需要重新下发配置
    |
    */
    'node_conf_path' => storage_path('app/'),

    /*
     * 管理服务列表
     */
    'kernel_servers' => ['cron_manager', 'node_manager'],

    /*
     * web socket 配置 暂时不可用
     */
    'socket' => [
        'host' => '0.0.0.0',
        'port' => 9501,
    ],
];
