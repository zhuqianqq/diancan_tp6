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
use app\servicestaff\Order as SF;

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

        $dingcanStauts = SF::analyseSysConfig($sysConf);

        if($dingcanStauts['isDingcanDay'] == 0){
            return ['list'=>[],'dingcanStauts'=>$dingcanStauts];
        }

        if($dingcanStauts['send_time_key'] == 1){
            $eat_type = 2;//中餐
        }else if($dingcanStauts['send_time_key'] == 2){
            $eat_type = 4;//晚餐
        }

        //获取当天订单详情中对应（中、晚餐的）餐馆id,菜名,单价,总点餐数,总价信息
        $OrderDetails = OrdD::where(['company_id'=>$userInfo->company_id,'eat_type'=>$eat_type])
                ->field('eatery_id,food_name,price,SUM(report_num) AS total_num,SUM(price) AS total_price')
                ->whereTime('create_time','today')
                ->group('eatery_id,food_name,price')
                ->select()->toArray();

        if(!$OrderDetails) return ['list'=>[],'dingcanStauts'=>$dingcanStauts];
 
        $list_key = $list = [];

        foreach ($OrderDetails as $k => $v) {
            //获取相关菜品的点餐人员姓名
            $eater_names = OrdD::where(['company_id'=>$userInfo->company_id,'eat_type'=>$eat_type,'eatery_id'=>$v['eatery_id'],'food_name'=>$v['food_name']])
            ->whereTime('create_time','today')
            ->column('staff_name');
            $v['eater_names'] = array_unique($eater_names);

            $list[$v['eatery_id']][] = $v;
           
        }

        //获取餐馆id对应的餐馆名称
        foreach ($list as $k2 => $v2) {
           $list_key[] = ER::getEateryName($k2);
        }

        $list=array_combine($list_key,$list);

         return ['list'=>$list,'dingcanStauts'=>$dingcanStauts];


    }


}
