<?php
declare (strict_types=1);

namespace app\middleware;

use app\util\CacheHelper;
use think\facade\Log;

class RateLimit
{
    protected $redis;
    public function __construct()
    {
        $this->redis = CacheHelper::getRedisConn();
    }

    private $interfaceData = [
        '//api/order/submit' => [//员工订餐接口
            'uri' => '//api/order/submit',
            'secNum' => 49,//每秒允许的最大并发数
            //'dayNum' => 10,//单个接口每天总的访问量
        ],
        '//api/order/getSysconfig' => [//获取系统配置
            'uri' => '//api/order/getSysconfig',
            'secNum' => 47,//单个接口每秒访问数
           // 'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/isOrder' => [//判断今日有无订单记录
            'uri' => '//api/order/isOrder',
            'secNum' => 48,//单个接口每秒访问数
            //'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/index' => [//订餐首页接口
            'uri' => '//api/order/index',
            'secNum' => 45,// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/myOrder' => [//订单详情接口
            'uri' => '//api/order/myOrder',
            'secNum' => 40,// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/cancelOrder' => [//取消订单接口
            'uri' => '//api/order/cancelOrder',
            'secNum' => 35,// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
    ];

    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        $request_uri = $request->request()['s'] ?? '';
        if (!$request_uri) {
            throw new \app\MyException(11101);
        }

        $currentUrlData = $this->interfaceData[$request_uri] ?? '';
        if (!$currentUrlData) return $next($request);

        $this->redis->select(1);

        if (!$this->minLimit($currentUrlData)) {
            throw new \app\MyException(10016);
        }

        return $next($request);
    }

    /**
     * 接口限流
     * @param $urlData
     * @return bool
     */
    public function minLimit($urlData)
    {
        $minNumKey = $urlData['uri'] . '_secNum';
        $resMin = $this->rateCheck($minNumKey, $urlData['secNum']);
        if (!$resMin['status']) {
            return false;
        }

        return true;
    }

    /**
     * 令牌桶限流算法
     * @param $key
     * @param $initNum
     * @param $expire
     * @return array
     */
    public function rateCheck($key, $initNum)
    {
        $nowTime = time();
        $result = ['status' => true, 'msg' => ''];

        $this->redis->watch($key); //命令用于监视一个(或多个) key ，如果在事务执行之前这个(或这些) key 被其他命令所改动，那么事务将被打断
        $limitVal = $this->redis->get($key);

        if ($limitVal) {
            $limitVal = json_decode($limitVal, true);
            $newNum = min($initNum, ($limitVal['num'] - 1) + (1 / $initNum) * ($nowTime - $limitVal['time']));
            //Log::info($newNum . '__' . ($nowTime - $limitVal['time']));
            if ($newNum > 0) {
                $redisVal = json_encode(['num' => $newNum, 'time' => time()]);
            } else {
                return ['status' => false, 'msg' => '当前时刻令牌消耗完！'];
            }
        } else {
            $redisVal = json_encode(['num' => $initNum, 'time' => time()]);
        }

        try {
            $this->redis->multi();//开启事务
            $this->redis->set($key, $redisVal);//更新缓存中的值
            $rob_result = $this->redis->exec();//提交事务e
            if (!$rob_result) {
                $result = ['status' => false, 'msg' => '访问频次过多！'];
            }
        } catch (\Exception $e) {
            throw new \app\MyException(10016, $e->getMessage());
        }

        return $result;
    }
}
