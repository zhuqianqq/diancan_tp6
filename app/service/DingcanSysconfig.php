<?php
declare (strict_types=1);
namespace app\service;

use app\model\DingcanSysconfig as DF;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;

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
     * 订餐设置
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
        $userInfo = CompanyAdmin::where('userid = :user_id', ['user_id' => $user_id])->find();
        if (!$userInfo) {
            throw new MyException(15002);
        }

        $data['company_id'] = $userInfo['company_id'];
        $oneSys = DF::where('company_id=:company_id', ['company_id' => $data['company_id']])->find();
        if (!$oneSys) {//新增
            try {
                $sysConfig = new DF;
                $sysConfig->save($data);
            } catch (\Exception $e){
               throw new MyException(15004, $e->getMessage());
            }
        } else {
            try {
                $oneSys->save($data);
            } catch (\Exception $e){
                throw new MyException(15004, $e->getMessage());
            }
        }

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
        $staffInfo = CompanyAdmin::where('userid=:userid', ['userid' => $userId])->find();
        if (!$staffInfo) {
            throw new MyException(10001);
        }
        $company_id = $staffInfo->company_id;
        //获取订餐设置
        $sysConf = DF::where('company_id=:company_id', ['company_id' => $company_id])->find();
        if ($sysConf) return $sysConf->toArray();
        return [];
    }

}
