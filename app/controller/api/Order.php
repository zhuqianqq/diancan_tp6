<?php
declare (strict_types=1);

namespace app\controller\api;

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Order as VO;
use think\annotation\route\Validate;
use think\exception\ValidateException;
use app\service\Order as SF;
use app\service\Eatery as SE;

/**
 * 订餐接口
 * Class Order
 * @package app\controller\api
 * @author  2066362155@qq.com
 * @Group("api/Order")
 */
class Order extends Base
{

    /**
     * 订单首页
     * @Route("index", method="GET")
     */
    public function index()
    {
        $result = SE::getlists();
        return $result;
    }

    /**
     * 提交订单
     * @Route("submit", method="POST")
     * @Validate(VO::class,scene="save",batch="true")
     */
    public function submit()
    {
        $data = input('post.');
        $result = SF::submit($data);
        return json_ok($result);
    }

    /**
     * 我的订单
     * @Route("index", method="GET")
     */
    public function myOrder()
    {
        $user_id = input('get.user_id');
        $result = SF::detail($user_id);
        return $result;
    }
}
