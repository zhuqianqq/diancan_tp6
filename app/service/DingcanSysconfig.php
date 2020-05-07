<?php
declare (strict_types=1);
namespace app\service;

use app\model\DingcanSysconfig as DF;
use app\MyException;
use app\traits\ServiceTrait;

/**
 * 订餐设置
 * Class Food
 * @package app\service
 * @author  2066362155@qq.com
 */
class DingcanSysconfig
{
    use ServiceTrait;

    /**
     * 更新或者创建菜品
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function setting($data)
    {
        //获取用户信息
        $user_id = input('user_id', '', 'int');
        if (!$user_id) {
            return json_error(15001);
        }
        $userInfo = getUserInfoById($user_id);
        if (!$userInfo) {
            throw new MyException(15002);
        }
        $data['company_id'] = $userInfo['company_id'];

        $oneSys = DF::where('company_id', $data['company_id'])->find();
        if (!$oneSys) {//新增
            try {
                $sysConfig = new DF;
                $sysConfig->save($data);
            }catch (\Exception $e){
               throw new MyException(15004, $e->getMessage());
            }
        } else {
            try {
                $oneSys->save($data);
            }catch (\Exception $e){
                throw new MyException(15004, $e->getMessage());
            }
        }

        return [];
    }

}
