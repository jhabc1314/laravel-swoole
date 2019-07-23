<?php
/**
 * Created by PhpStorm.
 * User: jackdou
 * Date: 19-7-11
 * Time: 下午3:22
 */

namespace JackDou\Swoole\Management;


use Illuminate\Support\Facades\Log;
use JackDou\Management\Models\Crontab;
use JackDou\Swoole\Exceptions\NotFoundException;

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
        $timer_id = swoole_timer_tick($crontab->cron_timer * 1000, function ($timer_id, $crontab) {

            try {
                $dir = config('swoole.cron_manager.running_log_path');
                if (empty($dir)) {
                    throw new NotFoundException('please make sure swoole.cron_manager.running_log_path is not null');
                }
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                $log_file = $dir . $crontab->id . '.log';
                file_put_contents($log_file, date('Y-m-d H:i:s') . " start..." . PHP_EOL, FILE_APPEND);
                $command = trim($crontab->cron_command);
                $cmd_args = explode(' ', $command);
                $cmd = [];
                foreach ($cmd_args as $arg) {
                    $cmd[] = escapeshellarg($arg);
                }
                $cmd = implode(' ', $cmd);
                $res = exec($cmd);
                file_put_contents($log_file, date('Y-m-d H:i:s') . " end...print:" . $res . PHP_EOL, FILE_APPEND);
            } catch (\Throwable $e) {
                if (!empty($log_file)) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " something wrong:" . $e->getMessage() . PHP_EOL, FILE_APPEND);
                } else {
                    Log::error($e->getMessage());
                }
            }
        }, $crontab);
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

    public static function getLog(int $log_id, int $start, int $end)
    {
        $log_file = config('swoole.cron_manager.running_log_path') . $log_id . '.log';
        $cmd = "sed -n '{$start},{$end}p' {$log_file}";
        exec($cmd, $output);
        return self::success($output);
    }

    public static function crontabs(string $ip)
    {
        $crontabs = Crontab::where('cron_node_ip', $ip)
            ->where('cron_node_status', 1)
            ->get();
        return self::success($crontabs);
    }

    public static function save(Crontab $crontab)
    {
        return self::success($crontab->save());
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