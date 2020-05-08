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
use app\service\Food as F;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Food as VF;
use think\annotation\route\Validate;
use think\exception\ValidateException;

/**
 * 菜品接口
 * Class Food
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/Food")
 */
class Food extends Base
{
    use ControllerTrait;

    /**
     * 根据餐馆id获取餐馆菜品信息
     * @Route("getEateryFoods", method="POST")
     */
    public function getEateryFoods()
    {
        header("Access-Control-Allow-Origin: *");
        $data = input('post.');
        $result = F::getInfo($data);
        return json_ok($result);
    }

    /**
    * 更新或者创建餐馆
    *  @Validate(VF::class,scene="save",batch="true")
    * @Route("addOrUpdata", method="POST")
    */
    public function addOrUpdata()
    {
        $data = input('post.');
        $result = F::addOrUpdata($data);
        return json_ok($result);
    }

    /**
     * 删除菜品
     * @Route("delete", method="GET")
     * @Validate(VF::class,scene="delete",batch="true")
     */
    public function delete()
    {
        $food_id = input('get.food_id');
        if (!$food_id) {
           return json_error('14001');
        }

        $result = F::deleteFood($food_id);
        return json_ok($result);
    }

}