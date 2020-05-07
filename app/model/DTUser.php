<?php
declare (strict_types=1);

namespace app\model;

use think\Model;
use app\traits\ModelTrait;
use think\facade\Db;

/**
 * 用户
 * Class User
 * @package app\model
 * @author  2066362155@qq.com
 */
class DTUser extends Model
{

    use ModelTrait;

    protected $table = "dc_company_staff";

    protected $pk = "staffid";

    //注册
    public function registerStaff($user_info,$cropId){
    	$data = [];
    	$data['staff_name'] = $user_info->name ?? '';
    	$data['department_id'] = $user_info->department[0] ?? '';
    	$data['avatar'] = $user_info->avatar ?? '';
    	$data['cropid'] = $cropId;
    	$data['platform_staffid'] = $user_info->userid ?? '';
    	$data['staff_status'] = $user_info->active ? 1 : 0;
    	$data['register_time'] = date('Y-m-d H:i:s',time());

    	$data['company_id'] = Db::table("dc_company_register")
        ->where('corpid',$cropId)
        ->value('company_id');
    	
    	return self::save($data);
    }
}

