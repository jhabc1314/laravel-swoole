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
        'name' => 'swoole',
        'host' => '0.0.0.0',
        'port' => '8821',
        'mode' => SWOOLE_PROCESS, //模式 BASE 基本 Reactor模式, PROCESS 进程模式
        'sock_type' => SWOOLE_TCP, //server 类型
        'setting' => [
            'worker_num' => 1, //work 进程数，内部采用协程，设置和cpu核数一致或多一个
            'max_request' => 1000, //同步无状态的server work 进程超过此最大请求数后会自动退出，释放内存
            'task_worker_num' => 1, //任务进程数，大于0即开启任务进程
            'task_max_request' => 1000, //设置task进程的最大任务数
            'dispatch_mode' => 1, //数据包分发模式，具体参见文档
            'daemonize' => false, //守护进程模式,关闭，使用supervisor管理比较合适
            'backlog' => 128, //同时可以保持的最大等待连接数
            'log_file' => storage_path('log/swoole.log'), //swoole运行日志的保存位置
            'pid_file' => storage_path('log/server.pid'), //pid保存的文件
            'open_eof_check' => 1,
            'package_eof' => "\r\n",
            'open_length_check' => true, //开启包长检测
            'package_length_type' => 'N', //长度类型
            'package_body_offset' => 16, //包体偏移量
            'package_length_offset' => 8, //协议中的包体长度字段在第几字节
            'tcp_defer_accept' => 2, //tcp延迟收包时间
            'buffer_output_size' => 2 * 1024 * 1024, //输出缓存区的最大值
            'socket_buffer_size' => 10 * 1024 * 1024, //客户端最大允许的待发送数据
            'enable_unsafe_event' => null, //dispatch_mode 设置为1，3时有效
            'discard_timeout_request' => null, //超时请求的处理方式，有效条件同上
            'enable_reuse_port' => null, //Linux内核3.9以上可用，端口重用
            'reload_async' => null, //安全重启开关
            'tcp_fastopen' => null, //快速握手特性
            'enable_coroutine' => true, //默认使用协程
            'max_coroutine' => 3000, //默认3000
            'task_enable_coroutine' => true, //协程支持任务进程
        ],
    ]
];
