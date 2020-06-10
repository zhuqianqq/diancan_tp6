<?php
/**
 */

namespace app\util;

use think\cache\driver\Redis;
use think\facade\Cache;

class CacheHelper
{
    private static $redisInstance;

    /**
     * 私有化构造函数
     * 原因：防止外界调用构造新的对象
     */
    private function __construct()
    {
    }


    /**
     * 获取redis连接的唯一出口
     */
    static public function getRedisConn()
    {
        if (!self::$redisInstance instanceof self) {
            self::$redisInstance = new self;
        }

        // 获取当前单例
        $temp = self::$redisInstance;
        // 调用私有化方法
        return $temp->connRedis();
    }

    /**
     * 连接ocean 上的redis的私有化方法
     * @return Redis
     */
    static private function connRedis()
    {
        $options = [
            'host'       => 'r-bp11ggbzfhgtsoz2rh.redis.rds.aliyuncs.com',
            'port'       => 6379,
            'password'   => 'zaqWSX987',
            'select'     => 1,
            'timeout'    => 0,
            'expire'     => 0,
            'persistent' => false,
            'prefix'     => '',
            'tag_prefix' => 'tag:',
            'serialize'  => [],
        ];
        try {
            $redis_ocean = new Redis($options);
        } catch (\Exception $e) {
            throw new \app\MyException(10016, $e->getMessage());
        }

        return $redis_ocean;
    }

    /*
    * 禁止clone
    */
    private function __clone()
    {
    }


    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->_redis->get($key);
    }

    /**
     * @param $key
     * @param $val
     * @param $expire
     */
    public function set($key, $val, $expire = 0)
    {
        $this->_redis->setnx($key, $val);
        if ($expire) {
            $this->_redis->expire($key, $expire);
        }
    }

    /**
     * @param $key
     * @param $val
     * @param $expire
     */
    public function setnx($key, $val, $expire = 0)
    {
        $this->_redis->setnx($key, $val);
        if ($expire) {
            $this->_redis->expire($key, $expire);
        }
    }

    /**
     * sadd
     * @param string $key
     * @param string $val
     */
    public function sadd($key, $val)
    {
        return $this->_redis->sadd($key, $val);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function incr($key)
    {
        return $this->_redis->incr($key);
    }

    /**
     * sismember
     * @param string $key
     * @param string $val
     */
    public function sismember($key, $val)
    {
        return $this->_redis->sismember($key, $val);
    }

    /**
     * @param $key
     * @param $val
     * @return mixed
     */
    public function lpush($key, $val)
    {
        return $this->_redis->lpush($key, $val);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function rpop($key)
    {
        return $this->_redis->rpop($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function lpop($key)
    {
        return $this->_redis->lpop($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function watch($key)
    {
        return $this->_redis->watch($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function multi()
    {
        return $this->_redis->multi();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function exec()
    {
        return $this->_redis->exec();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function select($key)
    {
        return $this->_redis->select($key);
    }
}
