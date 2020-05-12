<?php
declare (strict_types=1);

namespace app\controller\api;

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Order as VO;
use think\annotation\route\Validate;
use think\exception\ValidateException;
use app\servicestaff\Order as SF;
use app\servicestaff\Eatery as SE;

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

        return json_ok(['list' => $eateryList, 'dingcanStauts' => $dingcanStauts]);
    }

    /**
     * 提交订单
     * @Route("submit", method="POST")
     * @Validate(VO::class,scene="save",batch="true")
     */
    public function submit()
    {
        $data = input('param.');
        $sysConf = SF::getSysConfigById($data['staffid']);
        $status = checkDingcanStauts($sysConf);
        if ($status['isDingcanDay'] == 1 && $status['DingcanStauts'] == 1) {
            $result = SF::submit($data);
            return json_ok($result);
        }

        return json_error(16004);
    }

    /**
     * 我的订单
     * @Route("myOrder", method="GET")
     */
    public function myOrder()
    {
        $user_id = input('get.user_id','','int');
        $eatery_id = input('get.eatery_id','','int');
        if (!$user_id || !$eatery_id) {
            return json_error(10002);
        }
        $result = SF::detail($user_id, $eatery_id);
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
        $token = input('token','');
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
