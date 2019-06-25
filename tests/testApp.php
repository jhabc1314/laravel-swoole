<?php
/**
 *
 * User: jiangheng
 * Date: 2019/6/23
 * Time: 14:25
 */

namespace JackDou\Swoole\Tests;

class testApp
{
    public function func2($p1)
    {
        return self::response($p1 . 'yes!');
    }

    public static function func1($p1, $p2)
    {
        return self::response($p1 . $p2);
    }

    public static function response($data)
    {
        return [
            'code' => 0,
            'data' => $data
        ];
    }
}