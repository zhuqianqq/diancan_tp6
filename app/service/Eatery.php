<?php
declare (strict_types=1);
namespace app\service;

use app\model\Eatery as E;
use app\model\EateryRegister as ER;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;
use think\Db;
use app\service\DingcanSysconfig as SD;
use app\model\Order as Ord;
use app\model\OrderDetail as OrdD;
use app\model\SysArea;
/**
 * 菜品
 * Class Eatery
 * @package app\service
 * @author  2066362155@qq.com
 */
class Eatery
{

    /**
     * 餐馆管理列表
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function getlists()
    {
        $user_id = input('user_id', '', 'int');
        if (!$user_id) {
            throw new MyException(13001);
        }
        $userInfo = getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }
        $where = ['is_delete'=>0,'company_id'=>$userInfo->company_id];
        $eateryArr = [];
        $eatery = E::where($where)->order('create_time','asc')->field('eatery_id')->select();
        foreach ($eatery as $v){
            $eateryArr[] = $v['eatery_id'];
        }
        $list = ER::with(['food'])->select($eateryArr);
       
        if($list) {
            foreach ($list as $k => $v) {
               $list[$k]->province_name = SysArea::getAreaName($v->proive);
               $list[$k]->city_name = SysArea::getAreaName($v->city);
               $list[$k]->district_name = SysArea::getAreaName($v->district);
            }
            return $list->toArray();
        }
        return [];
    }

    /**
     * 获取指定的餐馆和菜品信息
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function getlist()
    {
        $user_id = input('user_id', '', 'int');
        $eatery_id = input('eatery_id', '', 'int');

        if (!$user_id || !$eatery_id) {
            throw new MyException(13001);
        }

        $eateryInfo = ER::find($eatery_id);
        if (!$eateryInfo) {
            throw new MyException(13002);
        }

        $userInfo = getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }
        $where = ['is_delete'=>0, 'company_id'=>$userInfo->company_id, 'eatery_id'=>$eatery_id];
        $list = E::with(['food'])->where($where)->select();
        if ($list) {
            return $list->toArray();
        }
        return [];
    }

    /**
     * 根据餐馆id获取餐馆名称
     */
    public static function getNameById($eateryId)
    {
        if (!$eateryId) {
            return json_error(13001);
        }
        $eatryInfo = E::where('eatery_id',$eateryId)->find();
        if ($eatryInfo) return $eatryInfo->toArray();
        return [];
    }

    /**
     * 最近订餐
     */
    public static function getRecentlyOrders()
    {
        $user_id = input('user_id', '', 'int');
     
        if (!$user_id) {
            throw new MyException(13001);
        }

        $userInfo = getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }

        $sysConf = SD::getSysConfigById($user_id);

        $dayStart = date('Y-m-d 00:00:00',time());
        $dayEnd = date('Y-m-d 23:59:59',time());
       $list = [];
        // $OrderDetails = OrdD::where(['company_id'=>$userInfo->company_id,'eat_type'=>2])
        //         ->whereBetween('create_time',[$dayStart,$dayEnd])
        //         ->select();
        // $list = [];

        // foreach($OrderDetails as $k => $v){
        //     $list[$v['eatery_id']][$v['food_name']]['food_name'] = $v->food_name;
        //     $list[$v['eatery_id']][$v['food_name']]['eater_name'][] = $v->staff_name;
        //     if(!isset($list[$v['eatery_id']][$v['food_name']]['nums'])){
        //         $list[$v['eatery_id']][$v['food_name']]['nums'] = 0;
        //     }
        //     $list[$v['eatery_id']][$v['food_name']]['nums'] += $v->report_num;
        //     if(!isset($list[$v['eatery_id']][$v['food_name']]['price'])){
        //         $list[$v['eatery_id']][$v['food_name']]['price'] = 0;
        //     }
        //     $list[$v['eatery_id']][$v['food_name']]['price'] += $v->price;
        // }

        $OrderDetails = OrdD::where(['company_id'=>$userInfo->company_id,'eat_type'=>2])
                ->field('eatery_id,food_name,SUM(report_num) AS total_num,SUM(price) AS total_price')
                ->whereTime('create_time','today')
                ->group('eatery_id,food_name')
                ->select()->toArray();

        foreach ($OrderDetails as $k => $v) {
            $eater_names = OrdD::where(['company_id'=>$userInfo->company_id,'eat_type'=>2,'eatery_id'=>$v['eatery_id'],'food_name'=>$v['food_name']])
            ->whereTime('create_time','today')
            ->column('staff_name');

            $v['eater_names'] = array_unique($eater_names);
            $list[$v['eatery_id']][] = $v;
           
        }
        foreach ($list as $k2 => $v2) {

           $list[$k2]['eatery_name'] = ER::getEateryName($k2);
        }

        //$list[$v['eatery_id']]['eatery_name'] = ER::getEateryName($v->eatery_id);

        dd($list);

        $where = ['is_delete'=>0,'company_id'=>$userInfo->company_id];
        $eateryArr = [];
        $eatery = E::where($where)->order('create_time','asc')->field('eatery_id')->select();
        foreach ($eatery as $v){
            $eateryArr[] = $v['eatery_id'];
        }
        $list = ER::with(['food'])->select($eateryArr);
        if($list) return $list->toArray();
        return [];


    }


}
