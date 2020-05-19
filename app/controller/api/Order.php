<?php
declare (strict_types=1);

namespace app\controller\api;

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Order as VO;
use think\annotation\route\Validate;
use think\exception\ValidateException;
use app\servicestaff\OrderService as SF;
use app\servicestaff\EateryService as SE;

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
        $staffid = input('get.user_id', '');
        $eateryList = SE::getEaterylists();
        $sysConf = SF::getSysConfigById($staffid);
        $dingcanStauts = SF::analyseSysConfig($sysConf);
        $eateryListFilter = SE::filerEatertList($eateryList, $sysConf);

        return json_ok(['list' => $eateryListFilter, 'dingcanStauts' => $dingcanStauts]);
    }

    /**
     * 提交订单
     * @Route("submit", method="POST")
     * @Validate(VO::class,scene="save",batch="true")
     */
    public function submit()
    {
        $data['report_num'] = input('param.report_num', '0', 'int');
        $data['eatery_id'] = input('param.eatery_id', '', 'int');
        $data['report_amount'] = input('param.report_amount', '0', 'string');
        $data['staffid'] = input('param.staffid', '', 'int');
        $data['orderInfo'] = input('param.orderInfo');
        $data['eat_type'] = input('param.eat_type', '', 'int');
        $data['order_id'] = input('param.order_id');
        $sysConf = SF::getSysConfigById($data['staffid']);
        $status = checkDingcanStauts($sysConf);

        if ($status['isDingcanDay'] == 1 && $status['DingcanStauts'] == 1) {
            $result = SF::submit($data);
            if (!$result) return json_error(10001);
            return json_ok($result);
        }

        return json_error(10001);
    }

    /**
     * 我的订单
     * @Route("myOrder", method="GET")
     */
    public function myOrder()
    {
        $user_id = input('get.user_id','','int');
        if (!$user_id) {
            return json_error(10002);
        }

        $result = SF::detail($user_id);
        $sysConf = SF::getSysConfigById($user_id);
        $result['dingcanStauts'] = SF::analyseSysConfig($sysConf);

        return json_ok($result);
    }

    /**
     * 获取系统订餐设置
     * @Route("getSysconfig", method="GET")
     */
    public function getSysconfig()
    {
        $user_id = input('get.user_id');
        if (!$user_id) {
            return json_error(10002);
        }
        $sysConf = SF::getSysConfigById($user_id);
        return json_ok($sysConf);
    }

    /**
     * 判断今天有无订餐
     * @Route("isOrder", method="GET")
     */
    public function isOrder()
    {
        $user_id = input('get.user_id');
        if (!$user_id) {
            return json_error(10002);
        }
        $result = SF::isOrder($user_id);
        return json_ok($result);
    }


    /**
     * H5 餐馆订单详情
     * @Route("eateryOrderDetail", method="GET")
     */
    public function eateryOrderDetail()
    {
        $token = urldecode(input('token',''));
        if(!$token){
            return json_error(10001);
        }

        $data = getH5token($token);
        $eatery_id = $data['eatery_id'] ?? '';
        $eat_type = $data['eat_type'] ?? '';
        if(!$eatery_id || !$eat_type){
            return json_error(10001);
        }
    
        $result = SF::getH5OrderDetail($eatery_id,$eat_type);
       
        return json_ok($result);
    }
}
