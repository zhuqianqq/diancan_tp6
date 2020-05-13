<?php
declare (strict_types=1);
namespace app\servicestaff;

use app\model\CompanyStaff;
use app\model\Eatery as E;
use app\model\EateryRegister as ER;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;
use think\Db;

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
        if (!$eateryId) {
            return json_error(13001);
        }
        $eatryInfo = E::where('eatery_id',$eateryId)->find();
        if ($eatryInfo) return $eatryInfo->toArray();
        return [];
    }
}
