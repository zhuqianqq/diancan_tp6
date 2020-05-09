<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/2
 * Time: 18:48
 */

namespace app\controller\admin;

use app\controller\admin\Base;
use app\traits\ControllerTrait;
use app\service\DingcanSysconfig as SD;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\DingcanSysconfig as VDS;
use think\annotation\route\Validate;
use think\exception\ValidateException;

/**
 * 系统设置
 * Class DingcanSysconfig
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/DingcanSysconfig")
 */
class DingcanSysconfig extends Base
{
    //验证器名称
    public static $validateName = 'DingcanSysconfig';

    use ControllerTrait;

    /**
    * 更新或者创建餐馆
     * @Validate(VDS::class,scene="save",batch="true")
    * @Route("setting", method="POST")
    */
    public function setting()
    {
        $result = SD::setting(input('param.'));
        return json_ok($result);
    }

    /**
     * 获取系统订餐设置
     * @Route("info", method="GET")
     */
    public function info()
    {
        $user_id = input('get.user_id','');
        if (!$user_id) {
            return json_error(10002);
        }
        $sysConf = SD::getSysConfigById($user_id);

        return json_ok($sysConf);
    }

}