<?php
declare (strict_types=1);
namespace app\service;

use app\model\CompanyAdmin;
use app\MyException;
use app\traits\ServiceTrait;
use app\service\DingcanSysconfigService;
use app\model\CompanyRegister;
use app\model\CompanyStaff;
use app\model\Food;
use app\model\EateryRegister;
use app\model\Eatery;
use app\model\DingcanSysconfig as DS;
use app\model\DTDepartment;
use app\util\AccessKeyHelper;

/**
 * 首页
 * Class IndexService
 * @package app\service
 * @author  2066362155@qq.com
 */
class IndexService
{

    use ServiceTrait;

    /**
     * 首页有无系统设置
     */
    public static function isSet($userId)
    {
        //获取系统设置
        $sysConf = DingcanSysconfigService::getSysConfigById($userId);
        return $sysConf;
    }

    /**
     * 首页系统设置
     */
    public static function setting($data)
    {
        if (!$data['user_id']) {
            throw new MyException(10001);
        }

        $data['dc_date'] = 0;//默认选择工作日
        if (isset($data['food_info'])) {
            try {
                $foodInfo = json_decode($data['food_info'], true);
            } catch (\Exception $e) {
                throw new MyException(14005);
            }
        }

        if (isset($data['news_time_type']) && !empty($data['news_time_type'])) {
            $data['end_time_type'] = 0;//默认订餐截止时间为送餐前30分钟
            $data['news_time_type'] = 1;//默认自动消息提醒时间为送餐前1小时
        }

        //组装送餐时间字段、消息提醒使时间字段
        $twoOclock = strtotime(date('Y-m-d 14:00:00',time()));
        $settedTime = date('Y-m-d ');
        $settedTime .= $data['mealTime'];
        $settedTime = strtotime($settedTime);
        $sendMessageTime = $settedTime - 3600;
        if ($settedTime > $twoOclock) {//当前时间大于2点即为晚餐
            $mealType = EateryRegister::EAT_TYPE_DINNER;
            $data['send_time_info'] = ['4'=>$data['mealTime']];
            $data['news_time'] = ['4'=>$sendMessageTime];
        } else {//中餐
            $mealType = EateryRegister::EAT_TYPE_LUNCH;
            $data['send_time_info'] = ['2'=>$data['mealTime']];
            $data['news_time'] = ['2'=>$sendMessageTime];
        }

        self::beginTrans();
        //添加系统配置表
        $userInfo = CompanyAdmin::getAdminInfoById($data['user_id']);
        if (!$userInfo) {
            throw new MyException(14002);
        }

        $dsM = new DS;
        $dsM->company_id = $userInfo['company_id'];
        $dsM->send_time_info = \GuzzleHttp\json_encode($data['send_time_info']);
        $dsM->news_time = \GuzzleHttp\json_encode($data['news_time']);
        $dsM->end_time_type = $data['end_time_type'];
        $dsM->dc_date = $data['dc_date'];
        try {
            $dsM->save();
        } catch (\Exception $e){
            throw new MyException(10001,$e->getMessage());
        }

        if (isset($data['eatery_id']) && !empty($data['eatery_id'])) {//新增了餐馆，不是默认餐馆
            //添加新增餐馆对应的菜品
            foreach ($foodInfo as $k => $v) {
                if (!checkMoney($v)) {
                    throw new MyException(14005);
                }
                $foodM = new Food;
                $foodM->eatery_id = $data['eatery_id'];
                $foodM->food_name = $k;
                $foodM->price = $v;
                try {
                    $foodM->save();
                } catch (\Exception $e){
                    throw new MyException(10001,$e->getMessage());
                }
            }
        } else {//默认餐馆
            //添加默认餐馆
           $erM = new EateryRegister;
           $eM = new Eatery;
           try {
               //添加餐馆注册表
               $erM->eatery_name = '默认餐馆';
               $erM->contacts = '';
               $erM->mobile = '';
               $erM->password = md5('123456');
               $erM->proive = '';
               $erM->city = '';
               $erM->district = '';
               $erM->address = '';
               $erM->eat_type = '';

               try {
                   $erM->save();
               } catch (\Exception $e){
                   throw new MyException(10001,$e->getMessage());
               }

               //添加餐馆表
               $eM->eatery_alias_name = '默认餐馆';
               $eM->company_id = $userInfo['company_id'];
               $eM->eatery_id = $erM->eatery_id;
               $eM->eat_type = $mealType;
               try {
                   $eM->save();
               } catch (\Exception $e){
                   throw new MyException(10001,$e->getMessage());
               }

               //添加菜品表
               foreach ($foodInfo as $k => $v) {
                   if (!checkMoney($v)) {
                       throw new MyException(14005);
                   }
                   $foodM = new Food;
                   $foodM->eatery_id = $eM->eatery_id;
                   $foodM->food_name = $k;
                   $foodM->price = $v;
                   try {
                       $foodM->save();
                   } catch (\Exception $e){
                       throw new MyException(10001,$e->getMessage());
                   }
               }
            } catch (\Exception $e){
               self::rollbackTrans();
               throw new MyException(10001,$e->getMessage());
            }
        }
        self::commitTrans();
        return [];
    }

    /**
     * 公司设置
     */
    public static function companySetting($data)
    {
        $companyAdminModel = new CompanyAdmin;
        $companyInfo = $companyAdminModel->where('userid',$data['user_id'])->find();
        $where = ['company_id'=>$companyInfo['company_id']];
        $allowField = ['contact','mobile','province','city','district','address'];

        try{
            $company = CompanyRegister::where($where)->find();
            $company->allowField($allowField)->save($data);
        }catch (\Exception $e){
            throw new MyException(10001, $e->getMessage());
        }

        return [];
    }

    /**
     * 获取公司设置
     */
    public static function getCompanySetting($user_id)
    {
        $companyAdminModel = new CompanyAdmin;
        $company_id = $companyAdminModel->where('userid = :user_id',['user_id'=>$user_id])->value('company_id');
        if(!$company_id){
            throw new MyException(20050);
        }
        
        return CompanyRegister::where('company_id',$company_id)->field('company_id,company_name,contact,mobile,province,city,district,address')->find();
    }


    /**
     * 获取管理员信息
     */
    public static function adminInfo($user_id)
    {

        $admin_info = CompanyAdmin::where('platform_userid = :user_id',['user_id'=>$user_id])
        ->field('userid,company_id,real_name,avatar,is_sys,corpid,platform_userid,department_id')
        ->find();

        if (!$admin_info) {
            throw new MyException(11104);
        }
       
        //判断该用户数据库是否有部门信息
        $DTDepartmentModel = new DTDepartment;
        $admin_info['hasDepartment'] = $DTDepartmentModel->where('company_id',$admin_info['company_id'])->count();

        //更新管理员登录信息
        CompanyAdmin::updateAdminInfo($admin_info['corpid'],$admin_info['platform_userid']);

        //生成access_key
        $admin_info['access_key'] = AccessKeyHelper::generateAccessKey($admin_info['user_id']); 

        return $admin_info;

    }
    

}
