<?php

namespace App\Lib;

class Redis
{
    /**
     * 连接redis单例（单机版本）
     *
     * @return \Redis
     */
    private static $_redisInstance = [];

    /**
     * 获取redis单例
     * @date 2022-05-10
     * @param int $db_select
     * @return \Redis
     */
    public static function redis(int $db_select = 1): \Redis
    {
        //增加单例，防止多次初始化redis，浪费性能
        if (empty(self::$_redisInstance[$db_select])
            || !self::$_redisInstance[$db_select] instanceof \Redis) {
            $con = new \Redis();
            $con->connect(env('REDIS_HOST'), env('REDIS_PORT'), 5);
            $con->auth(env('REDIS_PASSWORD')); // 密码验证
            self::$_redisInstance[$db_select] = $con;
        }

        if ($db_select > 0) {
            self::$_redisInstance[$db_select]->select($db_select); // 选择数据库
        }

        return self::$_redisInstance[$db_select];
    }
}
