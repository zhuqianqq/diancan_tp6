<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/2
 * Time: 18:48
 */

namespace app\controller\admin;

use app\controller\admin\Base;
use app\service\EateryRegisterService;
use think\annotation\route\Group;
use think\annotation\Route;
use app\model\EateryRegister as ER;
use app\validate\EateryRegister as VE;
use app\traits\ControllerTrait;
use think\annotation\route\Validate;
use think\exception\ValidateException;



/**
 * 餐馆注册接口
 * Class EateryRegister
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/EateryRegister")
 */
class EateryRegister extends Base
{
    //服务，带命名空间
    public static $service = 'app\service\EateryRegisterService';
    //验证器名称
   /* public static $validateName = 'EateryRegister';
    //保存验证场景
    public static $validateScene = 'save';*/

    use ControllerTrait;

    /**
     * 更新或者创建餐馆
     *  @Validate(VE::class,scene="save",batch="true")
     * @Route("addOrUpdata", method="POST")
     */
    public function addOrUpdata()
    {
        $data = input('post.');
        $result = EateryRegisterService::registerEatery($data);
        return json_ok($result);
    }

    /**
     * 删除餐馆
     * @Route("deleteEatery", method="POST")
     * @Validate(VE::class,scene="delete",batch="true")
     */
    public function deleteEatery()
    {
        $result = EateryRegisterService::delete();
        return json_ok($result);
    }

}