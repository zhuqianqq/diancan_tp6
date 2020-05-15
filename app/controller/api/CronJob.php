<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\controller\api\Base;
use app\controller\api\Dingtalk;
use app\model\CompanyRegister;
use app\model\DingcanSysconfig as DS;
use app\MyException;
use think\annotation\Route;
use think\annotation\route\Group;
use think\facade\Cache;

/**
 * 非用户身份类接口
 * Class Index
 * @package app\controller\api
 * @author  2066362155@qq.com
 * @Group("api/CronJob")
 */
class CronJob extends Base {

	const MES_KEY = 'MessageKey:';

	public static function sendMessage() {
		try {
			//判断上午还是下午 14点之前为上午
			$no = date("H", time());
			$today = date('Ymd', time());
			if ($no < 14) {
				$send_time_key = 2;
			} else {
				$send_time_key = 4;
			}
			//检查相应公司是否已经发送过工作消息 （是否已经在redis中有相应缓存key）
			$checkKeys = CompanyRegister::column('company_id,corpid');
			$companyIds = [];
			foreach ($checkKeys as $k1 => $v1) {
				$key = self::MES_KEY . $today . ':' . $send_time_key . ':' . $v1['corpid'];
				if (!Cache::has($key)) {
					$companyIds[] = $v1['company_id'];
				}
			}

			if (!$companyIds) {
				return json_ok(); //已全部发送工作消息
			}

			$sysConfs = DS::where('company_id', 'in', $companyIds)->select()->toArray();
			//检查需要发送消息的companyIds
			$sendCompanyIds = [];

			foreach ($sysConfs as $k2 => $v2) {

				if (self::checkNewsTime($v2) === true) {
					$sendCompanyIds[] = $v2['company_id'];
				}
			}

			if (!$sendCompanyIds) {
				return json_error(20901);
			}

			$corpIds = CompanyRegister::where('company_id', 'in', $sendCompanyIds)->column('corpid');

			if (!$corpIds) {
				return json_error(20050);
			}

			$dingTalk = new Dingtalk();
			foreach ($corpIds as $k3 => $v3) {
				$res = $dingTalk->sendMessage($v3);
				if ($res !== false) {
					$key = self::MES_KEY . $today . ':' . $send_time_key . ':' . $v1['corpid'];
					Cache::set($key, 1, 86400); //缓存1天时间
				}
			}

			return json_ok();

		} catch (\Exception $e) {

			throw new MyException(10001, $e->getMessage());

		}

	}

	//检测是否是发送工作消息的时间
	public static function checkNewsTime($sysConfs) {
		if (!$sysConfs) {
			return false;
		}
		if (!$sysConfs['news_time_type']) {
			return false;
		}
		if (!$sysConfs['news_time']) {
			return false;
		}

		$dingcanStatus = checkDingcanStauts($sysConfs);

		//在订餐日且状态是订餐报名中的
		if ($dingcanStatus['isDingcanDay'] == 1 && $dingcanStatus['DingcanStauts'] == 1) {
			$news_time_arr = json_decode($sysConfs['news_time'], true);

			$news_time = $news_time_arr[$dingcanStatus['send_time_key']];

			$nowTimestamp = time();
			//现在时间早于消息通知时间 返回ture
			if ($nowTimestamp < $news_time) {
		
				return true;
			} else {
	
				return false;
			}
		} else {
			return false;
		}

	}

}
