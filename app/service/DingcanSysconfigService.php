<?php
declare (strict_types=1);
namespace app\service;

use app\model\DingcanSysconfig as DF;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;

/**
 * 订餐设置
 * Class DingcanSysconfigService
 *
 *
 * @package app\service
 * @author  2066362155@qq.com
 */
class DingcanSysconfigService
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
        if (!$oneSys) {
            throw new MyException(15002);
        }

        try {
            $newsTime = [];
            $sendTimeArr = json_decode($data['send_time_info']);
            if ($data['news_time_type']) {
                foreach ($sendTimeArr as $k => $v) {
                    $settedTime = date('Y-m-d ');
                    $settedTime .= $v;
                    $sendMessageTime = strtotime($settedTime) - sendMessageTimeType($data['news_time_type']);
                    $newsTime[$k] = $sendMessageTime;
                }
                $data['news_time'] = json_encode($newsTime);
            }
        } catch (\Exception $e) {
            throw new MyException(15002);
        }

        try {
            $oneSys->save($data);
        } catch (\Exception $e){
            throw new MyException(15004, $e->getMessage());
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
