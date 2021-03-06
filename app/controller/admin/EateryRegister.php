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

    use ControllerTrait;

    /**
     * 更新或者创建餐馆
     *  @Validate(VE::class,scene="save",batch="true")
     * @Route("addOrUpdata", method="POST")
     */
    public function addOrUpdata()
    {
        $data['user_id'] = input('param.user_id','', 'int');
        $data['eatery_id'] = input('param.eatery_id','', 'int');
        $data['eatery_name'] = input('param.eatery_name','', 'string');
        $data['contacts'] = input('param.contacts','', 'string');
        $data['mobile'] = input('param.mobile','', 'string');
        $data['proive'] = input('param.proive','', 'int');
        $data['city'] = input('param.city','', 'int');
        $data['district'] = input('param.district','', 'int');
        $data['address'] = input('param.address','', 'string');
        $data['eat_type'] = input('param.eat_type','', 'string');
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
        $result = EateryRegisterService::eateryDelete();
        if (!$result) return json_ok($result, 13005);
        return json_ok();
    }

}
