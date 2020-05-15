<?php
declare (strict_types=1);
namespace app\servicestaff;

use app\model\CompanyStaff;
use app\model\Eatery as E;
use app\model\EateryRegister as ER;
use app\model\EateryRegister;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;
use think\Db;

/**
 * 菜品
 * Class EateryService
 * @package app\service
 * @author  2066362155@qq.com
 */
class EateryService
{

    /**
     * 餐馆管理列表
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function getEaterylists()
    {
        $user_id = input('get.user_id', '', 'int');
        if (!$user_id) {
            throw new MyException(13001);
        }

        $userInfo = CompanyStaff::getUserInfoById($user_id);
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

        if($list) return $list->toArray();
        return [];
    }

    /**
     * 根据餐馆id获取餐馆名称
     */
    public static function getNameById($eateryId)
    {
        if (!$eateryId) return json_error(13001);
        $eatryInfo = E::where('eatery_id',$eateryId)->find();
        if ($eatryInfo) return $eatryInfo->toArray();
        return [];
    }

    /**
     * 根据公司设置和餐馆设置过滤餐馆列表
     */
    public static function filerEatertList($eateryList, $sysConf)
    {
        $sendTimeArr = \GuzzleHttp\json_decode($sysConf['send_time_info'], true);
        $sysConfEatTypeArr = array_keys($sendTimeArr);//公司设置的eat_type
        $sysConfEatType = implode(',',$sysConfEatTypeArr);

        if (!$eateryList || !$sysConf) return json_error(13001);

        $firstEatery = [];
        foreach ($eateryList as $k => $v) {
            if ($k == 0) $firstEatery = $eateryList[$k];
            if (count($sysConfEatTypeArr) == 1) {//公司只订一种餐  中餐或者晚餐
                if ($sysConfEatType == EateryRegister::EAT_TYPE_LUNCH || $sysConfEatType == EateryRegister::EAT_TYPE_DINNER) {
                    if (strpos($v['eat_type'], $sysConfEatType) === false) {
                        unset($eateryList[$k]);
                        continue;
                    }
                }
            } else {
                $twoOclock = strtotime(date('Y-m-d 14:00:00',time()));//下午两点时间戳
                $nowTime = time();
                if ($nowTime < $twoOclock) {//上午
                    if (strpos($v['eat_type'], (string)EateryRegister::EAT_TYPE_LUNCH ) === false) {
                        unset($eateryList[$k]);
                        continue;
                    }
                } else {//下午
                    if (strpos($v['eat_type'], (string)EateryRegister::EAT_TYPE_DINNER) === false) {
                        unset($eateryList[$k]);
                        continue;
                    }
                }
            }
        }

        //没有餐馆则取第一个
        if (count($eateryList) == 0) {
            return ['1'=>$firstEatery];
        }

        return $eateryList;
    }
}
