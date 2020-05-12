<?php
declare (strict_types=1);
namespace app\service;

use app\MyException;
use app\traits\ServiceTrait;
use app\service\DingcanSysconfig;
use app\model\CompanyRegister;
use app\model\CompanyStaff;

/**
 * 首页
 * Class Index
 * @package app\service
 * @author  2066362155@qq.com
 */
class Index
{

    use ServiceTrait;

    /**
     * 首页有无系统设置
     */
    public static function isSet($userId)
    {
        //获取系统设置
        $sysConf = DingcanSysconfig::getSysConfigById($userId);
        return $sysConf;
    }

    /**
     * 首页系统设置
     */
    public static function setting()
    {
        //todo
        //获取今天星期几


    }

    /**
     * 公司设置
     */
    public static function companySetting($data)
    {
        $companyStaffModel = new CompanyStaff;
        $companyInfo = $companyStaffModel->where('staffid',$data['user_id'])->find();
        $where = ['company_id'=>$companyInfo['company_id']];
        $allowField = ['contact','mobile','province','city','address'];

        try{
            $company = CompanyRegister::where($where)->find();
            $company->allowField($allowField)->save($data);
        }catch (\Exception $e){
            throw new MyException(14005, $e->getMessage());
        }

        return [];
    }

    /**
     * 获取公司设置
     */
    public static function getCompanySetting($user_id)
    {
        $companyStaffModel = new CompanyStaff;
        $company_id = $companyStaffModel->where('staffid = :user_id',['user_id'=>$user_id])->value('company_id');
        if(!$company_id){
            throw new MyException(20050);
        }
        
        return CompanyRegister::where('company_id',$company_id)->field('company_id,company_name,contact,mobile,province,city,district,address')->find();
    }

}
