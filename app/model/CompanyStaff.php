<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/5
 * Time: 12:04
 */
namespace app\model;

use think\Model;
use app\traits\ModelTrait;

/**
 * 员工
 * Class CompanyStaff
 * @package app\model
 * @author  2066362155@qq.com
 */
class CompanyStaff extends Model
{
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';
    //只读字段，不允许被更改
    protected $readonly = [];
    //数据输出隐藏的属性
    protected $hidden = [];

    use ModelTrait;

    public  static $_table  = "dc_company_staff";

    /**
     * 根据用户corpId获取员工信息
     */
    public static function getDingdingUserIds($corpId){
    	return self::where('cropid = :corpId',['corpId' => $corpId])->column('platform_staffid');
    }

    /**
     * 根据用户id获取员工信息
     */
    public static function getUserInfoById($user_id)
    {
        $userInfo = self::where('staffid=:staffid', ['staffid' => $user_id])->find();
        return $userInfo;
    }

    /**
     * 根据员工id获取公司和部门信息
     */
    public static function getCompAndDeptInfoById($user_id)
    {
        $staffTable =  static::$_table;
        $result = \think\facade\Db::table($staffTable)
            ->alias('s')
            ->join(CompanyRegister::$_table . ' r', 'r.company_id = s.company_id')
            ->join(DTDepartment::$_table . ' d', 'd.company_id = s.company_id and d.platform_departid = s.department_id')
            ->where('staff_status = 1 and staffid=:staffid',['staffid' => $user_id])
            ->field(['staffid','s.company_id','staff_name','department_id','company_name','platform_departid','dept_name'])
            ->find();

        return $result;
    }
}