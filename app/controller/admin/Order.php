<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/2
 * Time: 18:48
 */
namespace app\controller\admin;

use app\controller\admin\Base;
use app\traits\ControllerTrait;
use app\service\FoodService as F;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Food as VF;
use think\annotation\route\Validate;
use think\exception\ValidateException;
use app\service\OrderService as O;


/**
 * 菜品接口
 * Class Food
 * @package app\controller\Order
 * @author  2066362155@qq.com
 * @Group("admin/Order")
 */
class Order extends Base
{
    use ControllerTrait;

    /**
     * 餐馆结算列表
     * @Route("lists")
     */
    public function lists()
    {
        $result = O::lists();
        return json_ok($result);
    }


    /**
     * 餐馆结算订单详情
     * @Route("orderDetails")
     */
    public function orderDetails()
    {
        $result = O::orderDetails();
        return json_ok($result);
    }

    /**
     * 订单详情 —— 删除订单
     * @Route("delOrder")
     */
    public function delOrder()
    {
        $result = O::delOrder();
        if($result === true){
            return json_ok();
        }else{
            return  json_error();
        }
        
    }

    /**
     * 订单详情 —— 修改订单金额
     * @Route("editOrder")
     */
    public function editOrder()
    {
        $result = O::editOrder();
        if($result === true){
            return json_ok();
        }else{
            return  json_error();
        }
        
    }


    /**
     * 餐馆结算
     * @Route("settlement")
     */
    public function settlement()
    {
        $result = O::settlement();
        return json_ok();
    }
}