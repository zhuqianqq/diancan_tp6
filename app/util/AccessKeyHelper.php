<?php
/**
 * api接口签名工具类
 */

namespace app\util;

use app\model\CompanyStaff;
use think\facade\Cache;
use think\facade\Log;

class AccessKeyHelper
{
    /**
     * @var string key前缀
     */
    public static $prefix = "acc_key:";

    /**
     * 获取缓存key
     * @param int $user_id
     * @param string $from
     * @return string
     */
    public static function getCacheKey($user_id, $from = "")
    {
        $key = self::$prefix . "{$user_id}";
        if (!empty($from)) {
            $key .= ":" . $from;
        }
        return $key;
    }

    /**
     * 从缓存中获取用户的access_key
     * @param int $user_id 用户id
     * @param string $from 客户端来源
     * @return string|null
     */
    public static function getAccessKey($user_id, $from = "")
    {
        $key = self::getCacheKey($user_id, $from);
        $res = Cache::get($key, null);
        return $res;
    }

    /**
     * 校验用户的access_key
     * @param int $user_id 用户id
     * @param string $access_key
     * @param string $from 客户端来源
     * @return bool
     */
    public static function validateAccessKey($user_id, $access_key, $from = "")
    {
        $acc_key = self::getAccessKey($user_id, $from);
        if (\think\facade\Env::get("APP_ENV") == "test") {
            Log::info("test环境:access_token：" . $user_id . ":" . "访问key:" . $access_key . "服务端key:" . $acc_key);
            return true;
        }
        if ($acc_key == $access_key) {
            return true;
        } else {
            Log::error("pro环境:access_token：" . $user_id . ":" . "生成key:" . $access_key . "服务端key:" . $acc_key);
            return false;
        }
    }

    /**
     * 为用户生成access_key
     * @param int $user_id 用户id
     * @param string $from 客户端来源
     */
    public static function generateAccessKey($user_id, $from = "")
    {
        $key = self::getCacheKey($user_id, $from);
        $acc_key = base64_encode(random_bytes(32));

        Cache::set($key, $acc_key, 7 * 24 * 3600); //缓存时间为7天
        Log::info("generate_access_key：" . $user_id . ":" . "生成key:" . $acc_key);
        return $acc_key;
    }

    /**
     * 从缓存中删除用户的access_key
     * @param int $user_id
     * @param string $from
     * @return bool
     */
    public static function forgetAccessKey($user_id, $from = "")
    {
        $key = self::getCacheKey($user_id, $from);
        return Cache::delete($key);
    }
}
