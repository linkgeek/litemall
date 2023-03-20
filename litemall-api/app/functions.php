<?php
// 应用公共文件

use think\Db;
use think\facade\Log;
use think\facade\Lang;

if (!function_exists('dd')) {
    function dd()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            echo '<pre>';
            if (is_bool($arg)) {
                var_dump($arg);
            } else {
                print_r($arg);
            }
        }
        exit;
    }
}

/**
 * 封装统一Api格式返回方法
 * @param $status ,状态码 使用配置文件接管不能输入数字
 * @param string $msg 响应信息
 * @param array $data 返回数据
 * @param int $code 返回的http状态码
 * @return \think\response\Json
 */
function show($status, $msg = '', $data = [], $code = 200)
{
    $result = [
        'status' => config('status.' . $status) ?? $status,
        'msg' => $msg,
        'data' => $data,
    ];
    return json($result, $code);
}

function return_format($status, $msg = '', $data = [], $code = 0)
{
    $result = [
        'success' => config('status.' . $status) ?? $status,
        'code' => $code,
        'message' => $msg,
        'data' => camelCase($data),
    ];
    return json($result, $code);
}

// {"error_code":0,"data":{"valid":false},"message":"ok"}
function return_mark($status, $msg = 'ok', $data = [], $code = 0)
{
    $result = [
        //'success' => config('status.' . $status) ?? $status,
        'error_code' => $code,
        'data' => $data,
        'message' => $msg,
    ];
    return json($result, $code);
}

function return_success($status, $data = [], $msg = 'ok', $code = 200)
{
    $result = [
        'success' => config('status.' . $status) ?? $status,
        'code' => $code,
        'message' => $msg,
        'data' => $data,
    ];
    return json($result, $code);
}

function return_error($status, $msg, $data = [], $code = 200)
{
    $result = [
        'success' => config('status.' . $status) ?? $status,
        'code' => $code,
        'message' => $msg,
        'data' => $data,
    ];
    return json($result, $code);
}

if (!function_exists('showError')) {
    /**
     * 自定义逻辑层异常处理
     * @param $errMsg
     * @param array $params
     * @param $errCode
     * @throws Exception
     */
    function showError($errMsg, $params = [], $errCode = null)
    {
        // TODO 多语言
        if (Lang::has($errMsg)) {
            $errMsg = !empty($params) ? Lang::get($errMsg, $params) : Lang::get($errMsg);
        }

        exception($errMsg, $errCode, '\app\common\exception\LogicException');
    }
}

if (!function_exists('exception')) {
    /**
     * 抛出异常处理
     * @param $errMsg
     * @param int $errCode
     * @param string $exception
     * @throws Exception
     */
    function exception($errMsg, $errCode = null, $exception = '')
    {
        $errCode = $errCode ?? config('status.system_error');
        $e = $exception ?: '\think\Exception';
        throw new $e($errMsg, [], $errCode);
    }
}

/**
 * 通用日志
 * @param $log_name
 * @param $log_content
 * @param int $cli
 */
function comLog($log_name, $log_content, $cli = 0)
{
    /*if ($cli == 1) {
        Log::init(['type' => 'File', 'path' => env('FILE.LOG_PATH') . "runtime/{$log_name}/"]);
    } else {
        Log::init(['type' => 'File', 'path' => ROOT_PATH . "runtime/{$log_name}" . DS]);
    }*/

    if (is_array($log_content)) {
        $log_content = json_encode($log_content, JSON_UNESCAPED_UNICODE);
    }

    Log::write($log_content);
}

/**
 * 自定义打印日志
 * @param string|array $msg 内容
 * @param string $pathFile 文件名
 * @param int $maxSize 单位M
 */
function debugLog($msg, $pathFile = 'debug.log', $maxSize = 2)
{
    clearstatcache();
    $path = dirname($pathFile);
    $realPath = $path == '.' ? '' : app()->getRuntimePath() . 'log/' . $path;
    if ($realPath && !is_dir($realPath)) {
        mkdir($realPath, 0755, true);
    }

    $filePath = app()->getRuntimePath() . 'log/' . $pathFile;
    $fileSize = file_exists($filePath) ? @filesize($filePath) : 0;
    $flag = $fileSize < max(1, $maxSize) * 1024 * 1024;
    $msgPrefix = '[' . date('Y-m-d H:i:s') . '] ';
    if (is_array($msg)) {
        $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    $msg = $msgPrefix . $msg . "\n\n";
    @file_put_contents($filePath, $msg, $flag ? FILE_APPEND : null);
}

function errorLog($action, $error, $cli = 0)
{
    $msg = $action . PHP_EOL . '============error=============start' . PHP_EOL . $error . PHP_EOL . '============error=============end';
    debugLog($msg, $pathFile = 'error.log', $maxSize = 2);
}

/**
 * 将数组中key下划线转换成小驼峰
 * @param $arr
 * @return array
 */
function camelCaseSimple($arr)
{
    if (!is_array($arr)) {   //如果非数组原样返回1
        return $arr;
    }
    $temp = [];
    foreach ($arr as $key => &$value) {
        $key1 = convertUnderline($key, FALSE);
        $value1 = camelCaseSimple($value);
        $temp[$key1] = $value1;
    }
    return $temp;
}

/**
 * 将下划线命名转换为驼峰式命名
 * @param $str
 * @param bool $uc_first
 * @return string|string[]
 */
function convertUnderline($str, $uc_first = true)
{
    $str = ucwords(str_replace('_', ' ', $str));
    $str = str_replace(' ', '', lcfirst($str));
    return $uc_first ? ucfirst($str) : $str;
}

/**
 * 将数组中key下划线转换成小驼峰
 * @param $arr
 * @return array
 */
function camelCase($arr)
{
    if (!is_array($arr)) {   //如果非数组原样返回
        return $arr;
    }
    $temp = [];
    foreach ($arr as $key => &$value) {
        //把数字格式的字段，保留两位或自定义的多少位小数（比如$data['amount|7'] = $amount）
        if (!is_array($value) && is_numeric($value) && strpos($value, '.') !== false) {
            if (strpos($key, '|') !== false) {
                $key_arr = explode('|', $key);
                $key = $key_arr[0];
                $value = keep($value, $key_arr[1]);
            } elseif (!strstr($value, '+')) {
                $value = keep($value);
            }
        }
        $key1 = convertUnderline($key, FALSE);
        $value1 = camelCase($value);
        $temp[$key1] = $value1;
    }
    return $temp;
}

/**
 * 保留小数
 * @param $num
 * @param int $float
 * @return string
 */
function keep($num, $float = 2)
{
    //即使是0，也返回0.00
    return bcdiv($num, 1, $float);
}

function curlRequest($url, $post = false, $param = array(), $https = false, $header = null, $show_error = false, $timeout = 30)
{
    $ch = curl_init($url);
    if ($post) {
        //设置请求方式和请求参数
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
    }
    // https请求，默认会进行验证
    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    }
    //设置header头
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    //curl_exec 执行请求会话（发送请求）
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $res = curl_exec($ch);
    if ($res === false && $show_error) {
        $res = curl_error($ch);
    }

    curl_close($ch);
    $res = json_decode($res, true);
    return $res;
}

if (!function_exists('getHomeMarkingDb')) {
    /**
     * @return mixed
     */
    function getHomeMarkingDb()
    {
        return \think\facade\Db::connect('homemaking');
    }
}

if (!function_exists('redis')) {
    // 获取容器对象实例
    function redis($db_select = 0): Redis
    {
        return \App\Lib\Redis::redis($db_select);
    }
}

function writeLogAndSendMessage($msg = '', $save_log_file = '', $send_msg = false)
{
    echo '[' . date('Y-m-d H:i:s') . ']' . $msg . PHP_EOL;

    //写日志
    if (!empty($save_log_file)) {
        comLog($save_log_file, $msg, 1);
    }

    //报警
    if ($send_msg) {
        // TODO
    }
}

