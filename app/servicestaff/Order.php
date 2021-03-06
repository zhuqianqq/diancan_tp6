<?php
declare (strict_types=1);
namespace app\servicestaff;

use app\model\Order as MO;
use app\model\OrderDetail as MD;
use app\model\Eatery;
use app\model\CompanyStaff;
use app\model\CompanyRegister;
use app\model\SysArea;
use app\model\DingcanSysconfig as DS;
use app\MyException;
use app\traits\ServiceTrait;
use app\service\EateryService as SE;
use think\Cache;
use think\facade\Db;
use app\model\OrderDetail as OrdD;


/**
 * 员工订餐
 * Class Order
 * @package app\service
 * @author  2066362155@qq.com
 */
class Order
{
    use ServiceTrait;

    /**
     * 订餐
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function submit($data)
    {
        $cacheKey = config('cachekeys.order_submit') . ":USERID:" . $data['staffid'];
        $redis = \think\facade\Cache::handler();
        $isLock = false;
        if ($redis->setnx($cacheKey, 1)) {
            $redis->expire($cacheKey, 1);
            $isLock = true;
        }
        if (!$isLock) {
            return WSTReturn("请不要重复提交");
        }

        $data['order_id'] = isset($data['order_id'])  && preg_match("/^[1-9][0-9]*$/" ,$data['order_id']) ? $data['order_id'] : 0;
        //获取员工信息
        $staffid = $data['staffid'];
        $sysConf = self::getSysConfigById($staffid);
        $compAndDeptInfo = CompanyStaff::getCompAndDeptInfoById($staffid);

        if(!$compAndDeptInfo){
            throw new MyException(20060);
        }

        foreach ($compAndDeptInfo as $k=>$v) {
            $data[$k] = $v;
        }
        //订单信息
        try {
            $data['orderArr'] = \GuzzleHttp\json_decode($data['orderInfo'], true);
        }catch (\Exception $e){
            throw new MyException(10001, $e->getMessage());
        }

        //获取餐馆信息
        $eateryName = SE::getNameById($data['eatery_id']);
        if (empty($eateryName)) {
            throw new MyException(13002);
        }
        $data['eatery_name'] = $eateryName;
        $data['department_name'] = $data['dept_name'];

        Db::startTrans();
        if ($data['order_id']==0) {//新增
            try {
                //新增订单表
                $orderM = new MO;
                $orderM->allowField(['company_id','company_name','eatery_id','eatery_name','staff_name','staffid','department_id','department_name','report_num','report_amount','is_settlement','create_time'])->save($data);

                //新增订单详情表
                foreach ($data['orderArr'] as $k=>$v) {
                    $orderDetailM = new MD;
                    $orderDetailM->company_id = $orderM->company_id;
                    $orderDetailM->order_id = $orderM->order_id;
                    $orderDetailM->staff_name = $data['staff_name'];
                    $orderDetailM->department_name = $data['department_name'];
                    $orderDetailM->food_name = $k;
                    $orderDetailM->price = $v;
                    $orderDetailM->report_num = 1;
                    $orderDetailM->report_amount = $v;
                    $orderDetailM->eat_type = $data['eat_type'];
                    $orderDetailM->eatery_id = $data['eatery_id'];
                    $orderDetailM->save();
                }

                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                throw new MyException(10001, $e->getMessage());
            }
        } else { //编辑
            try {
                if (!$data['order_id']) {
                    throw new MyException(16001);
                }
                $oneOrder = MO::where('order_id',$data['order_id'])->find();
                if (!$oneOrder) {
                    throw new MyException(16002);
                }
                $oneOrder->save($data);

                //获取订单详情 先删除后新增
                $oneOrder->orderDetail->delete();
                //新增订单详情表
                foreach ($data['orderArr'] as $k => $v) {
                    $orderDetailM = new MD;
                    $orderDetailM->company_id = $oneOrder->company_id;
                    $orderDetailM->order_id = $oneOrder->order_id;
                    $orderDetailM->staff_name = $data['staff_name'];
                    $orderDetailM->department_name = $data['department_name'];
                    $orderDetailM->food_name = $k;
                    $orderDetailM->price = $v;
                    $orderDetailM->report_num = 1;
                    $orderDetailM->report_amount = $v;
                    $orderDetailM->eat_type = $data['eat_type'];
                    $orderDetailM->eatery_id = $data['eatery_id'];
                    $orderDetailM->save();
                }

                Db::commit();
            }catch (\Exception $e){
                throw new MyException(10001, $e->getMessage());
                Db::rollback();
            }
        }

        return $sysConf;
    }

    /**
     * 我的订单详情
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function detail($userId){

        //获取员工对应的公司id
        $company_id = CompanyStaff::where('staffid', $userId)->value('company_id');
        if (!$company_id) {
            throw new MyException(10001);
        }

        //获取我的订单
        $where = ['company_id' => $company_id, 'staffid' => $userId];
        $todaytime=date('Y-m-d H:i:s',strtotime(date("Y-m-d"),time()));//今天零点
        $order = MO::where('company_id=:company_id and staffid=:staffid', $where)->where('create_time','>',$todaytime)->order('create_time', 'desc')->find();
        if (!$order) {
            $code = 16002;
            throw new MyException($code);
        }
        $orderDetail = $order->orderDetail;
        if (!$orderDetail) {
            $code = 16002;
            throw new MyException($code);
        }
        return ['order'=>$order];
    }

    /**
     * 判断今天有无订单
     */
    public static function isOrder($user_id)
    {
        //获取系统设置
        $sysConf = self::getSysConfigById($user_id);
        $where = ['company_id' => $sysConf['company_id'], 'staffid' => $user_id];
        $todaytime=date('Y-m-d H:i:s',strtotime(date("Y-m-d"),time()));//今天零点
        $order = MO::where($where)->where('create_time','>',$todaytime)->find();
        if ($order) return $order->toArray();
        return [];
    }

    /**
     * 根据用户ID获取订餐设置
     * @param $userId 用户id
     * @return array
     */
    public static function getSysConfigById($userId)
    {
        //获取员工信息
        $staffInfo = CompanyStaff::where('staffid', $userId)->find();
        if (!$staffInfo) {
            throw new MyException(10001);
        }
        $company_id = $staffInfo->company_id;

        //获取订餐设置
        $sysConf = DS::where('company_id', $company_id)->find();
        if (!$sysConf) {
            throw new MyException(10001);
        }

        return $sysConf->toArray();
    }


    public static function analyseSysConfig($sysConf)
    {
        if(!$sysConf){
            return [];
        }

        //订餐状态
        return checkDingcanStauts($sysConf);
    }


    //获取H5页面的餐馆订单数据 eatery_id：餐馆id  eat_type：中餐 1, 晚餐 2
    public static function getH5OrderDetail($eatery_id,$eat_type)
    {

        try {
               //获取当天相关餐馆订单详情中对应（中、晚餐的）菜名,单价,总点餐数,总价信息
               $order_details = OrdD::where(['eatery_id'=>$eatery_id,'eat_type'=>$eat_type])
                        ->field('food_name,price,SUM(report_num) AS total_num,SUM(price) AS total_price')
                        ->whereTime('create_time','today')
                        ->group('food_name,price')
                        ->select()->toArray();
               //获取对应公司信息
               $company_id = Eatery::where('eatery_id',$eatery_id)->value('company_id');
               $company_info = CompanyRegister::where('company_id',$company_id)->field('company_name,contact,mobile,province,city,district,address')->find();

               $province = SysArea::getAreaName($company_info->province) ?? '';
               $city = SysArea::getAreaName($company_info->city) ?? '';
               $district = SysArea::getAreaName($company_info->district) ?? '';
               $company_info->address_info = $province . $city . $district . $company_info->address;

               //获取配置的送餐时间
               $sysConf = DS::where('company_id', $company_id)->find();
               $send_time_arr = json_decode($sysConf['send_time_info'],true);

               if($eat_type == 2){  
                    $send_time_key = 1;//中餐
               }else if($eat_type == 4){
                    $send_time_key = 2;//晚餐
               }

               $company_info->send_time = $send_time_arr[$send_time_key];

               return ['order_details' => $order_details,'company_info' => $company_info];

           }catch (\Exception $e){

                throw new MyException(10001, $e->getMessage());
                  
           }
      
    }
    

}
