<?php
declare (strict_types=1);
namespace app\service;

use app\model\Eatery as E;
use app\model\EateryRegister as ER;
use app\model\Food;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;
use think\Db;
use app\service\DingcanSysconfigService as SD;
use app\model\Order as Ord;
use app\model\OrderDetail as OrdD;
use app\model\SysArea;
use app\servicestaff\OrderService as SF;
use app\service\EateryService as SE;

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
        $page_size= input('pagesize/d',10);
        $page= input('page/d',1);

        $where = [];
        if (!$user_id) {
            throw new MyException(13001);
        }
        $userInfo = CompanyAdmin::getAdminInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }
        $where['company_id'] = $userInfo->company_id;
        if (isset($eatery_id) && !empty($eatery_id)) {
            $eateryInfo = ER::find($eatery_id);
            if (!$eateryInfo) {
                throw new MyException(13002);
            }
            $where['eatery_id'] = $eatery_id;
        }
        //获取餐馆名称
        $eateryName = SE::getNameById($eatery_id);
        if (empty($eateryName)) {
            throw new MyException(13002);
        }

        if (isset($timeType)) {
            $timeInfo = getDateInfo($timeType);
            $where['start_time'] = $timeInfo['start_time'];
            $where['end_time'] = $timeInfo['end_time'];
        }

        $sql = "select date_format(create_time, '%Y-%m-%d') dat,eatery_id,eatery_name,
                        sum(report_amount) report_amount,sum(report_num) report_num
                     from dc_order where company_id=:company_id ";

        if ($eatery_id) {
            $sql .=  ' and eatery_id=:eatery_id ';
        }

        $sql .= " and create_time > :start_time and create_time <= :end_time 
                     group by date_format(create_time, '%Y-%m-%d'),eatery_id limit "
                     .($page-1)*$page_size. ','.$page_size;

        $res = \think\facade\Db::query($sql, $where);
        $last_page = intval(count($res)/$page_size) + 1;
        $page = [
            'total' => count($res),
            'per_page' => $page_size,
            'current_page' => $page,
            'last_page' => $last_page,
            'data' => $res,
        ];

        return $page;
    }

}
