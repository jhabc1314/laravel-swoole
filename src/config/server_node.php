<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-27
 * Time: 上午10:21
 */

return [
    /*
     * 服务名称 每个服务的唯一标识 根据需要进行修改
     */
    'swoole' => [
        [
            /*
             * 节点机器ip
             */
            'ip' => '127.0.0.1',

            /*
             * 监听端口
             */
            'port' => 8820,

            /*
             * 权重 0-100，根据算法分配请求到达哪个节点
             */
            'weight' => 100,

            /*
             * 节点状态 online 在线 offline 下线
             */
            'status' => 'online'
        ],
    ],
];