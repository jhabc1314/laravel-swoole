<?php
/**
 * Created by PhpStorm.
 * User: jackdou
 * Date: 19-7-11
 * Time: 下午3:22
 */

namespace JackDou\Swoole\Management;


class CrontabService
{
    /**
     * 启动调度任务
     *
     * @param Crontab $crontab
     *
     * @return array
     */
    public static function start($crontab)
    {
        $timer_id = $crontab->cron_id;
        if ($timer_id > 0) {
            //存在数据就先清除
            try {
                swoole_timer_clear($timer_id);
            } catch (\Exception $e) {

            }
        }
        $timer_id = swoole_timer_tick($crontab->cron_timer * 1000, function () use ($crontab) {
            $cmd = escapeshellcmd($crontab->cron_command);
            exec($cmd);
        });
        if ($timer_id > 0) {
            $crontab->cron_id = $timer_id;
            $crontab->cron_node_status = 1;
            return self::success($crontab);
        } else {
            return self::fail('设置失败');
        }
    }

    /**
     * 停止调度任务
     *
     * @param Crontab $crontab
     *
     * @return array
     */
    public static function stop($crontab)
    {
        $clear = true;
        $timer_id = $crontab->cron_id;
        if ($timer_id > 0) {
            try {
                $clear = swoole_timer_clear($timer_id);
            } catch (\Exception $e) {
                $clear = false;
            }
        }
        if ($clear) {
            $crontab->cron_id = 0;
            $crontab->cron_node_status = 0;
            return self::success($crontab);
        } else {
            $crontab->cron_id = -1;
            $crontab->cron_node_status = 0;
            //TODO 通知
            return self::success($crontab);
        }
    }


    public static function success($data = [])
    {
        return ['code' => 0, 'data' => $data];
    }

    public static function fail($msg, $code = -1)
    {
        return ['code' => $code, 'msg' => $msg];
    }

}