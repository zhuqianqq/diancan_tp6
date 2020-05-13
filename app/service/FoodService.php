<?php
declare (strict_types=1);
namespace app\service;

use app\model\Food as F;
use app\MyException;
use app\traits\ServiceTrait;
use app\service\EateryService;

/**
 * 菜品
 * Class FoodService
 * @package app\service
 * @author  2066362155@qq.com
 */
class FoodService
{

    //仓库，带命名空间
    public static $repository = 'app\repository\EateryRegisterRepository';

    use ServiceTrait;

    /**
     * 菜品列表
     */
    public static function getInfo()
    {
        $result = EateryService::getlist();
        return $result;
    }

    /**
     * 更新或者创建菜品
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function addOrUpdata($data)
    {
        $food_id = isset($data['food_id']) && preg_match("/^[1-9][0-9]*$/" ,$data['food_id']) ? $data['food_id'] : 0;
        try {
            $eateryArr = \GuzzleHttp\json_decode($data['eatrey_food_info'], true);
        }catch (\Exception $e){
            throw new MyException(14005, $e->getMessage());
        }

        if ($food_id==0) {//新增
            try {
                foreach ($eateryArr as $k => $v) {
                    $money_reg = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
                    if(!preg_match($money_reg, $v)){
                        throw new MyException(14005);
                    }
                    $foodM = new F;
                    $foodM->food_name = $k;
                    $foodM->price = $v;
                    $foodM->eatery_id = $data['eatery_id'];
                    $foodM->save();
                }
            }catch (\Exception $e){
                throw new MyException(14001, $e->getMessage());
            }
        } else { //编辑
            $oneFood = F::find($food_id);
            if (!$oneFood) {
                throw new MyException(14002);
            }
            try {
                $update_data = [];
                foreach ($eateryArr as $k => $v) {
                    $money_reg = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
                    if(!preg_match($money_reg, $v)){
                        throw new MyException(14005);
                    }
                    $update_data['food_id'] = $food_id;
                    $update_data['food_name'] = $k;
                    $update_data['price'] = $v;
                }
                $oneFood->save($update_data);
            }catch (\Exception $e){
                throw new MyException(14001, $e->getMessage());
            }
        }
        return [];
    }

    /**
     * 删除菜品
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function deleteFood($foodId){
        $oneFood = F::find($foodId);
        if (!$oneFood) {
            throw new MyException(14002);
        }
        if (!$oneFood->delete()) {
            throw new MyException(14004);
        }

        return true;
    }

}
