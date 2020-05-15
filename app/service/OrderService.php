<?php
declare (strict_types=1);
namespace app\service;

use app\model\Eatery as E;
use app\model\EateryRegister as ER;
use app\model\Food;
use app\model\Order;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;
use app\service\DingcanSysconfigService as SD;
use app\model\Order as Ord;
use app\model\OrderDetail as OrdD;
use app\model\SysArea;
use app\servicestaff\OrderService as SF;
use app\service\EateryService as SE;
use think\facade\Db;


/**
 * 订单结算
 * Class OrderService
 * @package app\service
 * @author  2066362155@qq.com
 */
class OrderService
{

    /**
     * 订单结算列表
     */
    public static function lists()
    {
        $user_id = input('user_id', '', 'int');
        $eatery_id = input('eatery_id', '', 'int');
        $timeType = input('timeType', '', 'string');
        $is_settlement = input('is_settlement', '', 'int');
        $start_time = input('start_time', '', 'string');
        $end_time = input('end_time', '', 'string');
        $page_size= input('pagesize/d',10);

        if (!$user_id) {
            throw new MyException(13001);
        }
        $userInfo = CompanyAdmin::getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }
        $where = 'company_id = :company_id ';
        $condition['company_id'] = $userInfo->company_id;
        if (isset($eatery_id) && !empty($eatery_id)) {
            $eateryInfo = ER::find($eatery_id);
            if (!$eateryInfo) {
                throw new MyException(13002);
            }
            $where .= ' AND eatery_id = :eatery_id';
            $condition['eatery_id'] = $eatery_id;
        }

        if (!empty($timeType)) {
            $timeInfo = getDateInfo($timeType);
        }
        if (!empty($start_time)) {
            $timeInfo['start_time'] = $start_time;
        }
        if (!empty($end_time)) {
            $timeInfo['end_time'] = $end_time . ' '. date('H:i:s');
        }
        if (isset($is_settlement)) {
            $where .= ' AND is_settlement = :is_settlement';
            $condition['is_settlement'] = $is_settlement;
        }

        $page = Order::whereRaw($where, $condition)->whereBetween('create_time',[$timeInfo['start_time'], $timeInfo['end_time']])
            ->field('date_format(create_time, \'%Y-%m-%d\') dat,eatery_id,eatery_name,is_settlement,settle_time,
                sum(report_amount) report_amount,sum(report_num) report_num')
            ->group('date_format(create_time, \'%Y-%m-%d\'),eatery_id')
            ->paginate($page_size)->toArray();

        $totalNum = 0;
        $totalMoney = 0;
        foreach ($page['data'] as $k => $v) {
            $totalNum += $v['report_num'];
            $totalMoney += $v['report_amount'];
        }

        $page['timeInfo'] = [
            'start_time' => date('Y-m-d', strtotime($timeInfo['start_time'])),
            'end_time' => date('Y-m-d', strtotime($timeInfo['end_time']))
        ];
        $page['totalNum'] = $totalNum;
        $page['totalMoney'] = $totalMoney;

        return $page;
    }

    /**
     * 订单结算详情
     */
    public static function orderDetails()
    {

        $user_id = input('user_id', '', 'int');
        $eatery_id = input('eatery_id', '', 'int');
        $date = input('date', '');
        $eat_type = input('eat_type', '');
  
        if (!$user_id || !$eatery_id || !$date) {
            throw new MyException(13001);
        }
        $eateryInfo = ER::find($eatery_id);
        if (!$eateryInfo) {
            throw new MyException(13002);
        }
        $userInfo = CompanyAdmin::getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }

        $where = ['company_id' => $userInfo->company_id, 'eatery_id' => $eatery_id];

        if($eat_type){

            if (strpos($eat_type,',') !== false) {
                 
               $eat_type = explode(',', $eat_type);
            }

            $where['eat_type'] = $eat_type;
        }

        //获取餐馆名称
        $eateryName = SE::getNameById($eatery_id);
        if (empty($eateryName)) {
            throw new MyException(13002);
        }

        $orderDetails = OrdD::where($where)
                        ->field('id,order_id,staff_name,food_name,price,eat_type,report_amount,report_num')
                        ->whereTime('create_time',$date)
                        ->select();

        $totalNum = $totalMoney = 0;
        foreach ($orderDetails as $k => $v) {
            $totalNum += $v->report_num;
            $totalMoney += $v->report_amount;
        }

        return ['orderDetails'=>$orderDetails,'eateryName'=>$eateryName,'totalNum'=>$totalNum,'totalMoney'=>$totalMoney];
    }


    /**
     * 订单结算——删除订单
     */
    public static function delOrder()
    {
         $order_id = input('order_id', '', 'int');
         if (!$order_id) {
            throw new MyException(13001);
         }

         try {
                 Db::startTrans();
                 Db::name("order")->where('order_id = :order_id',['order_id'=>$order_id])->delete();
                 Db::name("order_detail")->where('order_id = :order_id',['order_id'=>$order_id])->delete();
                 Db::commit();
         }catch (\Exception $e){
                 Db::rollback();
                 return false; 
         }
        
        return true;

    }


    /**
     * 订单结算——修改订单价格
     */
    public static function editOrder()
    {
         $order_id = input('order_id', '', 'int');
         $report_amount = input('money', '', 'float');
         if (!$order_id) {
            throw new MyException(13001);
         }

         try {
                 Db::startTrans();
                 Db::name("order")->where('order_id = :order_id',['order_id'=>$order_id])->update(['report_amount'=>$report_amount]);
                 Db::name("order_detail")->where('order_id = :order_id',['order_id'=>$order_id])->update(['report_amount'=>$report_amount]);
                 Db::commit();
         }catch (\Exception $e){
                 Db::rollback();
                 return false; 
         }
        
        return true;

    }



     /**
     * 餐馆结算
     */
    public static function settlement() {

        $user_id = input('user_id', '', 'int');
        $eatery_id = input('eatery_id', '');
        $date = input('date', '');

        if (!$user_id || !$date) {
            throw new MyException(13001);
        }

        $userInfo = CompanyAdmin::getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }

        $where = [];
        $where[] = ['company_id','=',$userInfo->company_id];
        $where[] = ['is_settlement','<>',1];

        $eatery_id = str_replace('，',',',$eatery_id);
        $date = str_replace('，',',',$date);

        if (strpos($eatery_id,',') !== false) {

            $eatery_id = array_unique(explode(',', $eatery_id));

            $where[] = ['eatery_id','in',$eatery_id];

        }else if($eatery_id){

            $where[] = ['eatery_id','=',$eatery_id];

        }


        $update_date = ['is_settlement' => 1,'settle_time' => date('Y-m-d H:i:s',time())];

        try {

                $orderModel = new Ord();
                $orderModel->startTrans(); // 开启订单模型的事务

                //处理'2020-05-10,2020-05-12,2020-05-13,2020-05-14'这样的传参
                if (strpos($date,',') !== false) {

                    $settlement_dates = [];
                    $_date = array_unique(explode(',', $date));
                    foreach ($_date as $k => $v) {

                        $settlement_dates[$k]['start_time'] = $v . ' 00:00:00';
                        $settlement_dates[$k]['end_time'] = $v . ' 23:59:59';
                    }
            
                    foreach ($settlement_dates as $k2 => $v2) {
                        $orderModel::where($where)
                        ->whereTime('create_time', 'between', [$v2['start_time'], $v2['end_time']])
                        ->update($update_date);
                    }

                //处理具体某天如'2020-05-14'的传参
                }else{

                    $orderModel::where($where)
                    ->whereTime('create_time', $date)
                    ->update($update_date);
                    
                }

                $orderModel->commit();

        }catch (\Exception $e){
        
                 $orderModel->rollBack();
                 throw new MyException(10001, $e->getMessage());
        }

        return true;

    }


    /**
     * 餐馆结算
     */
    public static function settlement_old() {
        $user_id = input('user_id', '', 'int');
        $eatery_id = input('eatery_id', '');
        $date = input('date', '');
        $timeType = input('timeType', '', 'string');

        if (!$user_id || !$date) {
            throw new MyException(13001);
        }

        // $eateryInfo = ER::find($eatery_id);
        // if (!$eateryInfo) {
        //     throw new MyException(13002);
        // }
        $userInfo = CompanyAdmin::getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }

        $where = [];
        $where[] = ['company_id','=',$userInfo->company_id];
        $where[] = ['is_settlement','<>',1];

        $eatery_id = str_replace('，',',',$eatery_id);
        $date = str_replace('，',',',$date);

        if (strpos($eatery_id,',') !== false) {

            $eatery_id = explode(',', $eatery_id);

            $where[] = ['eatery_id','in',$eatery_id];

        }else if($eatery_id){

            $where[] = ['eatery_id','=',$eatery_id];

        }


        $update_date = ['is_settlement' => 1,'settle_time' => date('Y-m-d H:i:s',time())];

        try {

                $orderModel = new Ord();
                $orderModel->startTrans(); // 开启订单模型的事务

                //处理'2020-05-10,2020-05-12,2020-05-13,2020-05-14'这样的传参
                if (strpos($date,',') !== false) {

                    $settlement_dates = [];
                    $_date = explode(',', $date);
                    foreach ($_date as $k => $v) {

                        $settlement_dates[$k]['start_time'] = $v . ' 00:00:00';
                        $settlement_dates[$k]['end_time'] = $v . ' 23:59:59';
                    }
            
                    foreach ($settlement_dates as $k2 => $v2) {
                        $orderModel::where($where)
                        ->whereTime('create_time', 'between', [$v2['start_time'], $v2['end_time']])
                        ->update($update_date);
                    }

                //处理全选餐馆的结算传参 若全选 需要传timeType参数（最近七天 上周 本月 最近30天的结算)
                }else if($date === 'ALL'){

                    $timeInfo = getDateInfo($timeType);
                    $orderModel::where($where)
                    ->whereTime('create_time', 'between', [$timeInfo['start_time'], $timeInfo['end_time']])
                    ->update($update_date);

                //处理全选餐馆的结算传参 若全选 且传的timeType为Search 结算该查询时间段的订单数据  参数为 2020-05-01|2020-05-15
                }else if($timeType === 'Search'){

                    $date = explode('|', $date);
                    $orderModel::where($where)
                        ->whereTime('create_time', 'between', $date)
                        ->update($update_date);

                //处理具体某天如'2020-05-14'的传参
                }else{

                    $orderModel::where($where)
                    ->whereTime('create_time', $date)
                    ->update($update_date);
                    
                }

                $orderModel->commit();

        }catch (\Exception $e){
        
                 $orderModel->rollBack();
                 throw new MyException(10001, $e->getMessage());
        }

        return true;

    }
    
    

}
