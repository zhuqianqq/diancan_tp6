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
use app\service\D as F;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\DingcanSysconfig as DS;
use think\annotation\route\Validate;
use think\exception\ValidateException;

/**
 * 系统设置
 * Class DingcanSysconfig
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/Food")
 */
class DingcanSysconfig extends Base
{
    //验证器名称
    public static $validateName = 'DingcanSysconfig';

    use ControllerTrait;

    /**
    * 更新或者创建餐馆
    *  @Validate(VF::class,scene="save",batch="true")
    * @Route("setting", method="POST")
    */
    public function setting()
    {
        $data = input('post.');
        $result = F::addOrUpdata($data);
        return json_ok($result, 14003);
    }

}