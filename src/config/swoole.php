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
        'host' => '127.0.0.1', //监听的本机ip，如果需要局域网通信则需要监听局域网ip
        'port' => 8820,
        'serialize_type' => 1, //序列化类型 1 serialize 2 json
        'namespace' => "App\\Services\\",//服务对应业务代码所在命名空间

        /*
        |--------------------------------------------------------------------------
        | 服务发现方式
        |--------------------------------------------------------------------------
        | 1 手动，使用 server_node_conf 选项内的节点配置信息
        | 2 统一模式，使用 jackdou/management 项目的后台下发管理 需要配合 jackdou/management 项目
        |
         */
        'node_find_type' => 1,

        'server_node_conf' => config_path('server_node.php'),//服务节点所在配置文件
        'setting' => [
            'log_file' => storage_path('logs/swoole.log'),
            'pid_file' => storage_path('logs/swoole.pid'),
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
        'host' => '127.0.0.1', //监听ip，如果需要局域网通信则需要监听局域网ip
        'port' => 8821,
        'serialize_type' => 1, //序列化类型 1 serialize 2 json
        'namespace' => "JackDou\\Management\\Services\\",//服务对应业务代码所在命名空间
        'node_find_type' => 2,
        'node_conf_path' => storage_path('app/node_manager.conf'),//服务节点所在配置文件
        'setting' => [
            'worker_num' => 2,
            'backlog' => 128, //同时可以保持的最大等待连接数
            'log_file' => storage_path('logs/node_manage.log'),
            'pid_file' => storage_path('logs/node_manage.pid'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 服务公用的配置选项
    |--------------------------------------------------------------------------
    | 如果需要不同的配置内容在上面的具体服务内配置
    | 优先级 服务单独配置 > 公共服务配置
    */
    'servers_setting' => [
        'worker_num' => swoole_cpu_num() + 1, //work 进程数，内部采用协程，设置和cpu核数一致或多一个
        'max_request' => 1000, //同步无状态的server work 进程超过此最大请求数后会自动退出，释放内存
        'task_worker_num' => 1, //任务进程数，大于0即开启任务进程
        'task_max_request' => 1000, //设置task进程的最大任务数
        'dispatch_mode' => 3, //数据包分发模式，具体参见文档
        'daemonize' => false, //守护进程模式,关闭，使用supervisor管理比较合适
        'backlog' => 128, //同时可以保持的最大等待连接数
        'enable_coroutine' => true, //默认使用协程
        'max_coroutine' => 3000, //默认3000
        'task_enable_coroutine' => true, //协程支持任务进程
    ],

    /*
     * web socket 配置 暂时不可用
     */
    'socket' => [
        'host' => '0.0.0.0',
        'port' => 9501,
    ],
];
