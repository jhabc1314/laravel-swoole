<?php
/**
 * Created by PhpStorm.
 * User: jiangheng
 * Date: 19-6-25
 * Time: 下午4:48
 */

namespace JackDou\Swoole\Facade;

use Illuminate\Support\Facades\Facade;

class Service extends Facade
{
    /**
     * 获取组件的注册名称。
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'service'; }
}