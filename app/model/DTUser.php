<?php
declare (strict_types=1);
namespace app\model;

use app\traits\ModelTrait;
use think\facade\Db;

/**
 * 用户
 * Class User
 * @package app\model
 * @author  2066362155@qq.com
 */
class DTUser extends BaseModel
{

    use ModelTrait;

    protected $table = "dc_company_staff";

    protected $pk = "staffid";

    //注册
    public function registerStaff($user_info,$cropId){
        $department_id = DTDepartment::where('platform_departid=:platform_departid', ['platform_departid' => $user_info->department[0]])->value('id');

        try {
            //根据平台department_id获取系统department_id
            Db::startTrans();
            $data = [];
            $data['staff_name'] = $user_info->name ?? '';
            //$data['department_id'] = $user_info->department[0] ?? '';
            $data['department_id'] = $department_id ?? '';
            $data['avatar'] = $user_info->avatar ?? '';
            $data['cropid'] = $cropId;
            $data['platform_staffid'] = $user_info->userid ?? '';
            $data['staff_status'] = $user_info->active ? 1 : 0;
            $data['register_time'] = date('Y-m-d H:i:s',time());

            $company_id =  Db::table("dc_company_register")
            ->where('corpid',$cropId)
            ->value('company_id');

            $data['company_id'] = $company_id;
            //如果该用户为管理员 增加公司管理员信息
            if($user_info->isAdmin === true ){
                $admin_data = [];
                $admin_data['company_id'] = $company_id;
                $admin_data['real_name'] = $data['staff_name'];
                $admin_data['avatar'] = $data['avatar'];
                $admin_data['is_sys'] = 1;
                $admin_data['corpid'] = $cropId;
                $admin_data['platform_userid'] = $data['platform_staffid'];
                $admin_data['department_id'] = $data['department_id'];
                $admin_data['login_time'] = $data['register_time'];
                $admin_data['login_ip'] = GetIp();
                $admin_data['is_enabled'] = $data['staff_status'];
                $admin_data['create_time'] = $data['register_time'];

                Db::table("dc_company_admin")->insert($admin_data);
            }
            Db::table("dc_company_staff")->insert($data);
            Db::commit();

        } catch (\Exception $e) {
                Db::rollback();
                throw new \app\MyException(20020, $e->getMessage());
        }
    	
    	return true;
    }


}





