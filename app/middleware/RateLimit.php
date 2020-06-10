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

    public function __construct()
    {
        $this->cache = CacheHelper::getRedisConn();
        $this->cache->select(1);
    }

    private $interfaceData = [
        '//api/order/submit' => [
            //员工订餐接口
            'uri' => 'api-order-submit',
            'secNum' => 49,//每秒允许的最大并发数
            //'dayNum' => 10,//单个接口每天总的访问量
        ],
        '//api/order/getSysconfig' => [
            //获取系统配置
            'uri' => 'api-order-getSysconfig',
            'secNum' => 47,//单个接口每秒访问数
            // 'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/isOrder' => [
            //判断今日有无订单记录
            'uri' => 'api-order-isOrder',
            'secNum' => 500,//单个接口每秒访问数
            //'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/index' => [
            //订餐首页接口
            'uri' => 'api-order-index',
            'secNum' => 45,// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/myOrder' => [
            //订单详情接口
            'uri' => 'api-order-myOrder',
            'secNum' => 40,// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/cancelOrder' => [
            //取消订单接口
            'uri' => 'api-order-cancelOrder',
            'secNum' => 35,// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
    ];

    protected function getCacheKey()
    {
        $ip = GetIp();
        $uri = $this->uri;
        return md5($ip . $uri);
    }

    function microtime_float()
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

        //接口时间限流，这种方式可以防止钻时间漏洞无限的访问接口 比如在59秒的时候访问，就钻了空子

        $key = $this->getCacheKey();
        $len = $this->cache->llen($key);
        if ($len === 0) {
            $this->cache->lpush($key, $this->microtime_float());
            $this->cache->expire($key, 1);
        } else {
            //判断有没有超过1分钟
            $max_time = $this->cache->lrange($key, 0, 0);
            //判断最后一次访问的时间比对是否超过了1秒钟

            if (($this->microtime_float() - $max_time[0]) < 1000) {
                if ($len > $secNum) {
                    //记录失败次数
                    $this->cache->inc('fail_num:' . $this->uri);
                    throw new \app\MyException(10016);
                } else {
                    $this->cache->lpush($key, $this->microtime_float());
                }
            }
        }
        return $next($request);
    }
}
