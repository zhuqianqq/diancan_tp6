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
 * 管理员
 * Class EateryRegister
 * @package app\model
 * @author  2066362155@qq.com
 */
class CompanyAdmin extends BaseModel
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


    public static function updateAdminInfo($cropId,$userid){

        self::where(['corpid'=>$cropId,'platform_userid'=>$userid])
        ->update(['login_time'=> date('Y-m-d H:i:s',time()),'login_ip'=> GetIp()]);

    }

    public static function isAdmin($cropId,$userid){

        return self::where(['corpid'=>$cropId,'platform_userid'=>$userid])->find();
    }

    /**
     * 根据用户id获取管理员信息
     */
    public static function getAdminInfoById($user_id)
    {
        $userInfo = self::where('userid=:userid', ['userid' => $user_id])->find();
        return $userInfo;
    }
}