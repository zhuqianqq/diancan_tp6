<?php
declare (strict_types=1);

namespace app\middleware;

use app\util\CacheHelper;

class RateLimit
{
    /**
     * 缓存对象
     */
    protected $cache;
    // uri
    protected $uri;

    private $interfaceData = [
        '//api/order/submit' => [
            //员工订餐接口
            'uri' => 'api-order-submit',
            'secNum' => 3000,//每分钟允许的最大并发数
            //'dayNum' => 10,//单个接口每天总的访问量
        ],
        '//api/Dingtalk/index' => [
            //员工订餐接口
            'uri' => 'api-Dingtalk-index',
            'secNum' => 3000,//每分钟允许的最大并发数
            //'dayNum' => 10,//单个接口每天总的访问量
        ],
        '//api/Dingtalk/DTGetUserInfo' => [
            //员工订餐接口
            'uri' => 'api-Dingtalk-DTGetUserInfo',
            'secNum' => 3000,//每分钟允许的最大并发数
            //'dayNum' => 10,//单个接口每天总的访问量
        ],
        '//api/order/getSysconfig' => [
            //获取系统配置
            'uri' => 'api-order-getSysconfig',
            'secNum' => 3000,//单个接口每分钟访问数
            // 'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/isOrder' => [
            //判断今日有无订单记录
            'uri' => 'api-order-isOrder',
            'secNum' => 3000,//单个接口每分钟访问数
            //'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/index' => [
            //订餐首页接口
            'uri' => 'api-order-index',
            'secNum' => 3000,// 单个接口每分钟访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/myOrder' => [
            //订单详情接口
            'uri' => 'api-order-myOrder',
            'secNum' => 3000,// 单个接口每分钟访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/cancelOrder' => [
            //取消订单接口
            'uri' => 'api-order-cancelOrder',
            'secNum' => 3000,// 单个接口每分钟访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
    ];

    protected function getRedis()
    {
        $this->cache = CacheHelper::getRedisConn();
        $this->cache->select(1);
    }

    protected function getCacheKey()
    {
        $uri = $this->uri;
        return md5($uri);
    }

    protected function microtime_float()
    {
        list($msec, $sec) = explode(" ", microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }

    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     */
    public function handle($request, \Closure $next)
    {
        $request_uri = $request->request()['s'] ?? '';
        if (!$request_uri) {
            throw new \app\MyException(11101);
        }

        $currentUrlData = $this->interfaceData[$request_uri] ?? '';
        if (!$currentUrlData) return $next($request);
        $this->uri = $currentUrlData['uri'];
        $secNum = $currentUrlData['secNum'];

        $this->getRedis();

        //接口时间限流，这种方式可以防止钻时间漏洞无限的访问接口 比如在59秒的时候访问，就钻了空子
        $expireTime = 60;
        // 统计所有的请求数
        $this->cache->inc('total:' . $this->uri);
        
        $key = $this->getCacheKey();
        $len = $this->cache->llen($key);
        if ($len === 0) {
            $this->cache->lpush($key, time());
            $this->cache->expire($key, $expireTime);
        } else {
            //判断有没有超过1分钟
            $max_time = $this->cache->lrange($key, 0, 0);
            //判断最后一次访问的时间比对是否超过了1分钟

            if ((time() - $max_time[0]) < $expireTime) {
                if ($len > $secNum) {
                    //记录失败次数
                    $this->cache->inc('fail_num:' . $this->uri);
                    throw new \app\MyException(10016);
                } else {
                    $this->cache->lpush($key, time());
                }
            }
        }
        return $next($request);
    }
}
