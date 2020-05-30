<?php
declare (strict_types = 1);
namespace app\service;

use app\model\CompanyAdmin;
use app\model\Eatery as E;
use app\model\EateryRegister as ER;
use app\model\OrderDetail as OrdD;
use app\model\SysArea;
use app\MyException;
use app\servicestaff\OrderService as SF;
use app\service\OrderService as OrdS;
use app\service\DingcanSysconfigService as SD;
use think\facade\Cache;

/**
 * 菜品
 * Class EateryService
 * @package app\service
 * @author  2066362155@qq.com
 */
class EateryService {

    const MES_KEY = 'MessageKey:';

	/**
	 * 餐馆管理列表
	 * @param array $data
	 * @return array 对象数组
	 * @throws \app\MyException
	 */
	public static function getlists() {
		$user_id = input('user_id');
		if (!$user_id) {
			throw new MyException(13001);
		}
		$userInfo = CompanyAdmin::getAdminInfoById($user_id);
		if (!$userInfo) {
			throw new MyException(13002);
		}

		$eateryArr = [];
		$eatery = E::where('is_delete=0 and company_id=:company_id', ['company_id' => $userInfo->company_id])->order('create_time', 'asc')->field('eatery_id')->select();
		foreach ($eatery as $v) {
			$eateryArr[] = $v['eatery_id'];
		}

		$list = ER::with(['food'])->select($eateryArr);
		if ($list) {
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
	public static function getlist() {
		$user_id = input('user_id', '', 'int');
		$eatery_id = input('eatery_id');
		if (!$user_id || !$eatery_id) {
			throw new MyException(13001);
		}
		$eateryInfo = ER::where('eatery_id=:eatery_id',['eatery_id'=>$eatery_id])->find();
		if (!$eateryInfo) {
			throw new MyException(13002);
		}
		$userInfo = CompanyAdmin::getAdminInfoById($user_id);
		if (!$userInfo) {
			throw new MyException(13002);
		}

		$where = ['company_id' => $userInfo->company_id, 'eatery_id' => $eatery_id];
		$list = E::with('food')->where('is_delete=0 and company_id=:company_id and eatery_id=:eatery_id', $where)->select();
		if ($list) {
			return $list->toArray();
		}

		return $list;
	}

	/**
	 * 根据餐馆id获取餐馆名称
	 */
	public static function getNameById($eateryId) {
		if (!$eateryId) {
			return json_error(13001);
		}
		$eatryName = E::where('eatery_id=:eatery_id', ['eatery_id' => $eateryId])->value('eatery_alias_name');

		return $eatryName;
	}

	/**
	 * 最近订餐
	 */
	public static function getRecentlyOrders() {
		$user_id = input('user_id', '', 'int');
		if (!$user_id) {
			throw new MyException(13001);
		}
		$userInfo = CompanyAdmin::getAdminInfoById($user_id);
		if (!$userInfo) {
			throw new MyException(13002);
		}

		$sysConf = SD::getSysConfigById($user_id);

		$dingcanStauts = SF::analyseSysConfig($sysConf);

		$eat_type = $dingcanStauts['send_time_key']; 
		
		$searchDay = 'today'; //默认查询今天的数据
		$dingcanStauts['recent_day'] = '';
		//如果当天为非工作日 查询最近一次工作日的订餐数据
		if ($dingcanStauts['isDingcanDay'] == 0) {
			$recentOrder = OrdD::where(['company_id' => $userInfo->company_id])
				->field('order_id,eat_type,create_time')
				->order('id','desc')
				->find();

			if (!empty($recentOrder['create_time'])) {
                $dingcanStauts['send_time_key'] = $eat_type = $recentOrder['eat_type'];
                $_searchDay = explode(' ', $recentOrder['create_time']);
                $searchDay = $_searchDay[0];
                $dingcanStauts['recent_day'] = $searchDay;
            }
		}

        //报名中状态判断是否发送了订餐消息
        if($dingcanStauts['DingcanStauts'] == 1){
            $today = date('Ymd', time());
            $key = self::MES_KEY . $today . ':' . $dingcanStauts['send_time_key'] . ':' . $userInfo->corpid;
            if (Cache::has($key)) {
                $isSendMsg = 1;
            }else{
                $isSendMsg = 0;
            }
        //非报名中订餐状态统一认为已经发送了订餐的工作消息提醒
        }else{
            $isSendMsg = 1;
        }
        

		//获取当天订单详情中对应（中、晚餐的）餐馆id,菜名,单价,总点餐数,总价信息
		$OrderDetails = OrdD::where(['company_id' => $userInfo->company_id, 'eat_type' => $eat_type])
			->field('eatery_id,food_name,price,SUM(report_num) AS total_num,SUM(price) AS total_price')
			->whereTime('create_time', $searchDay)
			->group('eatery_id,food_name,price')
			->select()->toArray();

		if (!$OrderDetails) {
			return ['list' => [], 'dingcanStauts' => $dingcanStauts,'isSendMsg' => $isSendMsg];
		}

		$list_key = $list = [];

		foreach ($OrderDetails as $k => $v) {
			//获取相关菜品的点餐人员姓名
			$eater_names = OrdD::where(['company_id' => $userInfo->company_id, 'eat_type' => $eat_type, 'eatery_id' => $v['eatery_id'], 'food_name' => $v['food_name']])
				->whereTime('create_time', 'today')
				->column('staff_name');
			$v['eater_names'] = array_unique($eater_names);
			$v['token'] = setH5token($v['eatery_id'], $eat_type);
			$list[$v['eatery_id']][] = $v;
		}

		//获取餐馆id对应的餐馆名称
		foreach ($list as $k2 => $v2) {
			$list_key[] = ER::getEateryName($k2);
		}
		$list = array_combine($list_key, $list);

		return ['list' => $list, 'dingcanStauts' => $dingcanStauts, 'isSendMsg' => $isSendMsg]; 
	}

	/**
	 * 最近订餐  获取对应餐馆的所有点餐人信息
	 */
	public static function getEatersList() {

		$user_id = input('user_id', '', 'int');
		$eatery_id = input('eatery_id', '', 'int');
		$food_name = input('food_name', '', 'string');

		if (!$user_id || !$eatery_id || !$food_name) {
			throw new MyException(13001);
		}

		$userInfo = CompanyAdmin::getAdminInfoById($user_id);
		if (!$userInfo) {
			throw new MyException(13002);
		}

		$sysConf = SD::getSysConfigById($user_id);

		$dingcanStauts = SF::analyseSysConfig($sysConf);

		$eat_type = $dingcanStauts['send_time_key']; 
		
		$searchDay = 'today'; //默认查询今天的数据
		$dingcanStauts['recent_day'] = '';
		//如果当天为非工作日 查询最近一次工作日的订餐数据
		if ($dingcanStauts['isDingcanDay'] == 0) {
			$recentOrder = OrdD::where(['company_id' => $userInfo->company_id])
				->field('order_id,eat_type,create_time')
				->order('id','desc')
				->find();
			$dingcanStauts['send_time_key'] = $eat_type = $recentOrder['eat_type'];
			$_searchDay = explode(' ', $recentOrder['create_time']);
			$searchDay = $_searchDay[0];
			$dingcanStauts['recent_day'] = $searchDay;
		}


		$where = ['company_id' => $userInfo->company_id, 'eatery_id' => $eatery_id, 'eat_type' => $eat_type, 'food_name' => $food_name];
		//获取当天订单详情中对应（中、晚餐的）餐馆id,菜名,单价,总点餐数,总价信息
		$OrderDetails = OrdD::where($where)
			->field('order_id,staff_name,food_name,report_num')
			->whereTime('create_time',$searchDay)
			->select()->toArray();
		//echo OrdD::getLastSql();die;	
		return ['orderDetails' => $OrderDetails];

	}


	 /**
     * 最近订餐 删除餐馆的相关点餐人信息
     */
	
	 public static function delEaterOrder() {

		$user_id = input('user_id', '', 'int');
	 	$order_id = input('order_id', '', 'int');

		if (!$user_id || !$order_id) {
			throw new MyException(13001);
		}

		$userInfo = CompanyAdmin::getAdminInfoById($user_id);
		if (!$userInfo) {
			throw new MyException(13002);
		}

		$sysConf = SD::getSysConfigById($user_id);

		$dingcanStauts = SF::analyseSysConfig($sysConf);

		if ($dingcanStauts['DingcanStauts'] != 1) {
				
				throw new MyException(16111);

		}

		return OrdS::delOrder();

	 }
}
