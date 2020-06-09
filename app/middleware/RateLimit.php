<?php
declare (strict_types=1);

namespace app\middleware;

use app\util\CacheHelper;
use think\Config;

class RateLimit
{
    /**
     * 缓存对象
     */
    protected $cache;

    /**
     * 配置参数
     * @var array
     */
    protected $config = [
        // 缓存键前缀，防止键值与其他应用冲突
        'prefix' => 'throttle_',
        // 节流规则 true为自动规则
        'key'    => true,
        // 节流频率 null 表示不限制 eg: 10/m  20/h  300/d
        'visit_rate' => '500/s',
        // 访问受限时返回的http状态码
        'visit_fail_code' => 10016,
        // 访问受限时访问的文本信息
        'visit_fail_text' => '访问频率受到限制，请稍等__WAIT__秒再试',
    ];

    public function __construct(Config $config)
    {
        $this->cache  = CacheHelper::getRedisConn();
    }

    protected $wait_seconds = 0;

    protected $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
    ];

    protected $need_save = false;
    protected $history = [];
    protected $key = '';
    protected $now = 0;
    protected $num_requests = 0;
    protected $expire = 1;
    protected $uri = '';

    private $interfaceData = [
        '//api/order/submit' => [//员工订餐接口
            'uri' => '//api/order/submit',
            'secNum' => '49/s',//每秒允许的最大并发数
            //'dayNum' => 10,//单个接口每天总的访问量
        ],
        '//api/order/getSysconfig' => [//获取系统配置
            'uri' => '//api/order/getSysconfig',
            'secNum' => '47/s',//单个接口每秒访问数
           // 'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/isOrder' => [//判断今日有无订单记录
            'uri' => '//api/order/isOrder',
            'secNum' => '500/s',//单个接口每秒访问数
            //'dayNum' => 500,//单个接口每天总的访问量
        ],
        '//api/order/index' => [//订餐首页接口
            'uri' => '//api/order/index',
            'secNum' => '45/s',// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/myOrder' => [//订单详情接口
            'uri' => '//api/order/myOrder',
            'secNum' => '40/s',// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
        '//api/order/cancelOrder' => [//取消订单接口
            'uri' => '//api/order/cancelOrder',
            'secNum' => '35/s',// 单个接口每秒访问数
            //'dayNum' => 500, //单个接口每天总的访问量
        ],
    ];

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

        $this->uri = $request_uri;
        $this->config['visit_rate'] = $currentUrlData['secNum'];

        $allow = $this->allowRequest($request);
        if (!$allow) {
            // 访问受限
            $code = $this->config['visit_fail_code'];
            $content = str_replace('__WAIT__', $this->wait_seconds, $this->config['visit_fail_text']);
            $this->cache->select(1);
            $this->cache->inc('fail_num' . ":" . $this->uri);
            throw new \app\MyException($code, $content);
        }
        $response = $next($request);
        if ($this->need_save && 200 == $response->getCode()) {
            $this->history[] = $this->now;
            $this->cache->set($this->key, $this->history, $this->expire);

            // 将速率限制 headers 添加到响应中
            $remaining = $this->num_requests - count($this->history);
            $response->header([
                'X-Rate-Limit-Limit' => $this->num_requests,
                'X-Rate-Limit-Remaining' => $remaining < 0 ? 0: $remaining,
                'X-Rate-Limit-Reset' => $this->now + $this->expire,
            ]);
        }
        return $response;
    }

    /**
     * 生成缓存的 key
     * @param Request $request
     * @return null|string
     */
    protected function getCacheKey($request)
    {
        $key = $this->config['key'];

        if ($key instanceof \Closure) {
            $key = call_user_func($key, $this, $request);
        }

        if (null === $key || false === $key || null === $this->config['visit_rate']) {
            // 关闭当前限制
            return;
        }

        if (true === $key) {
            $key = $request->ip();
        }

        return md5($this->config['prefix'] . $key . $this->uri);
    }

    /**
     * 解析频率配置项
     * @param $rate
     * @return array
     */
    protected function parseRate($rate)
    {
        list($num, $period) = explode("/", $rate);
        $num_requests = intval($num);
        $duration = $this->duration[$period] ?? intval($period);
        return [$num_requests, $duration];
    }

    /**
     * 计算距离下次合法请求还有多少秒
     * @param $history
     * @param $now
     * @param $duration
     * @return void
     */
    protected function wait($history, $now, $duration)
    {
        $wait_seconds = $history ? $duration - ($now - $history[0]) : $duration;
        if ($wait_seconds < 0) {
            $wait_seconds = 0;
        }
        $this->wait_seconds = $wait_seconds;
    }

    /**
     * 请求是否允许
     * @param $request
     * @return bool
     */
    protected function allowRequest($request)
    {
        $key = $this->getCacheKey($request);
        if (null === $key) {
            return true;
        }
        list($num_requests, $duration) = $this->parseRate($this->config['visit_rate']);
        $history = $this->cache->get($key, []);
        $now = time();

        // 移除过期的请求的记录
        $history = array_values(array_filter($history, function ($val) use ($now, $duration) {
            return $val >= $now - $duration;
        }));

        if (count($history) < $num_requests) {
            // 允许访问
            $this->need_save = true;
            $this->key = $key;
            $this->now = $now;
            $this->history = $history;
            $this->expire = $duration;
            $this->num_requests = $num_requests;
            return true;
        }

        $this->wait($history, $now, $duration);
        return false;
    }


    public function setRate($rate)
    {
        $this->config['visit_rate'] = $rate;
    }
}
