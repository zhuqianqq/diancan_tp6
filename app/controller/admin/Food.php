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
use app\service\FoodService as F;
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
        $result = F::getInfo();
        return json_ok($result);
    }

    /**
    * 更新或者创建菜品
    *  @Validate(VF::class,scene="save",batch="true")
    * @Route("addOrUpdata", method="POST")
    */
    public function addOrUpdata()
    {
        $data['food_id'] = input('param.food_id', '', 'int');
        $data['eatrey_food_info'] = input('param.eatrey_food_info', '');
        $data['eatery_id'] = input('param.eatery_id', '', 'int');
        $result = F::addOrUpdata($data);
        if (!$result['flag'])  return json_error($result['code'], $result['msg']);
        return json_ok($result);
    }

    /**
     * 删除菜品
     * @Route("delete", method="GET")
     * @Validate(VF::class,scene="delete",batch="true")
     */
    public function delete()
    {
        $food_id = input('param.food_id', '', 'int');
        if (!$food_id) return json_error('14001');
        $result = F::deleteFood($food_id);

        return json_ok($result);
    }

}