<?php
declare (strict_types=1);
namespace app\service;

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
    public static function getlists()
    {
        $user_id = input('user_id', '', 'int');
        if (!$user_id) {
            return json_error(13001);
        }
        $userInfo = getUserInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(13002);
        }
        $eateryArr = [];
        $eatery = E::where('is_delete = 0 AND company_id = '.$userInfo->company_id)->field('eatery_id')->select();
        foreach ($eatery as $v){
            $eateryArr[] = $v['eatery_id'];
        }
        $list = ER::with(['food'])->select($eateryArr);

        return json_ok($list->toArray(),13003);
    }

}
