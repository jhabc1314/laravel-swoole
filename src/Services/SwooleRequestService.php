<?php
/**
 *
 * User: jiangheng
 * Date: 2019/6/23
 * Time: 13:17
 */

namespace JackDou\Swoole\Services;

use JackDou\Swoole\Exceptions\SwooleRequestException;

class SwooleRequestService extends SwooleService
{
    const FUNC = 'func';
    const PARAMS = 'params';

    /**
     * 打包数据
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function pack($data)
    {
        $data = self::$config['serialize_type'] == 1 ? serialize($data) : json_encode($data);
        $response = pack('N', strlen($data)) . $data;
        $response .= "\r\n";
        return $response;
    }

    /**
     * 解包数据
     *
     * @param string $data
     *
     * @return mixed
     *
     * @throws SwooleRequestException
     */
    public static function unpack(string $data)
    {
        $receive = unpack('N', substr($data, 0, 4));
        $len = $receive[1];
        unset($receive);
        //只保留第一个收到的完整字符串
        $data = explode("\r\n", substr($data, 4))[0];
        if ($len != strlen($data)) {
            throw new SwooleRequestException("receive data length error:" . $len . strlen($data));
        }
        return self::$config['serialize_type'] == 1 ? unserialize($data) : json_decode($data, true);
    }

    /**
     * 执行调用的类函数 方法
     *
     * @param $request
     *
     * @throws SwooleRequestException
     *
     * @return mixed
     */
    public static function call($request)
    {
        if (strtolower($request[self::FUNC]) == 'ping') {
            return "pong";
        }
        try {
            $result = call_user_func_array(self::$config['namespace'] . $request[self::FUNC], $request[self::PARAMS]);
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