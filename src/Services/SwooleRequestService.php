<?php
/**
 * 请求处理
 *
 * User: jackdou
 * Date: 2019/6/23
 * Time: 13:17
 */

namespace JackDou\Swoole\Services;

use JackDou\Swoole\Exceptions\SwooleRequestException;

class SwooleRequestService
{

    /**
     * @var array 包传输配置
     */
    public static $pack_config = [
        'open_eof_check' => 1,
        'package_eof' => "\r\n",
        'open_length_check' => true, //开启包长检测
        'package_length_type' => 'N', //长度类型
        'package_body_offset' => 4, //包体偏移量
        'package_length_offset' => 0, //协议中的包体长度字段在第几字节
    ];

    const FUNC = 'func';
    const PARAMS = 'params';

    /**
     * 打包数据
     *
     * @param mixed $data
     * @param int $serialize_type
     *
     * @return string
     */
    public static function pack($data, $serialize_type = 1)
    {
        $data = $serialize_type == 1 ? serialize($data) : json_encode($data);
        $response = pack('N', strlen($data)) . $data;
        $response .= "\r\n";
        return $response;
    }

    /**
     * 解包数据
     *
     * @param string $data
     * @param int $serialize_type
     *
     * @return mixed
     *
     * @throws SwooleRequestException
     */
    public static function unpack(string $data, $serialize_type = 1)
    {
        $receive = unpack('N', substr($data, 0, 4));
        $len = $receive[1];
        unset($receive);
        //只保留第一个收到的完整字符串
        $data = explode("\r\n", substr($data, 4))[0];
        if ($len != strlen($data)) {
            throw new SwooleRequestException("receive data length error:" . $len . strlen($data));
        }
        return $serialize_type == 1 ? unserialize($data) : json_decode($data, true);
    }

    /**
     * 执行调用的类函数 方法
     *
     * @param $request
     * @param $namespace
     *
     * @throws SwooleRequestException
     *
     * @return mixed
     */
    public static function call($request, $namespace)
    {
        if (strtolower($request[self::FUNC]) == 'ping') {
            return "pong";
        }
        try {
            $result = call_user_func_array($namespace . $request[self::FUNC], $request[self::PARAMS]);
        } catch (\Throwable $exception) {
            throw new SwooleRequestException('call error:' . $exception->getMessage());
        }
        return  $result;
    }

    /**
     * 组合客户端发起请求的数据结构，待打包的数据
     *
     * @param string $call_func testClass::testfunc
     * @param array $params
     *
     * @return array
     */
    public static function getRequest(string $call_func, ?array $params = null) :array
    {
        return [
            self::FUNC => $call_func,
            self::PARAMS => $params,
        ];
    }

    /**
     * 系统发送错误时发送默认的响应结构
     *
     * @param string $err_msg
     *
     * @return string
     */
    public static function errorResponse($err_msg = '')
    {
        return self::pack(['msg' => $err_msg, 'code' => -99]);
    }


}