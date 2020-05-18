<?php
declare (strict_types=1);
namespace app\service;

use app\model\Food as F;
use app\MyException;
use app\traits\ServiceTrait;
use app\service\EateryService;
use think\facade\Db;

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
        $res = ['flag' => 1];
        //$food_id = isset($data['food_id']) && preg_match("/^[1-9][0-9]*$/" ,$data['food_id']) ? $data['food_id'] : 0;
        try {
            $eateryArr = \GuzzleHttp\json_decode($data['eatrey_food_info'], true);
        }catch (\Exception $e){
            throw new MyException(10001, $e->getMessage());
        }

        Db::startTrans();
        if ($data['food_id']) {//新增
            try {
                foreach ($eateryArr as $k => $v) {
                    //同一餐馆下不允许添加重复菜品
                    $food = F::where('eatery_id=:eatery_id and food_name=:food_name', ['eatery_id' => $data['eatery_id'], 'food_name' => $k])->find();
                    if ($food) {
                        return ['flag' => '0', 'code' => 14005, 'msg' => $k . '已存在，请勿重复添加'];
                        break;
                    }

                    $money_reg = '/(^[0-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
                    if(!preg_match($money_reg, $v)){
                        return ['flag' => '0', 'code' => 14005];
                        break;
                    }
                    $foodM = new F;
                    $foodM->food_name = $k;
                    $foodM->price = $v;
                    $foodM->eatery_id = $data['eatery_id'];
                    $foodM->save();
                }
            }catch (\Exception $e){
                Db::rollback();
                throw new MyException(10001, $e->getMessage());
            }
        } else { //编辑
            $oneFood = F::find($data['food_id']);
            if (!$oneFood) {
                throw new MyException(14002);
            }
            try {
                $update_data = [];
                foreach ($eateryArr as $k => $v) {
                    $money_reg = '/(^[0-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
                    if(!preg_match($money_reg, $v)){
                        return ['flag' => '0', 'code' => 14005, 'msg' => $k . '已存在，请勿重复添加'];
                        break;
                    }
                    $update_data['food_id'] = $data['food_id'];
                    $update_data['food_name'] = $k;
                    $update_data['price'] = $v;
                }
                $oneFood->save($update_data);
            }catch (\Exception $e){
                Db::rollback();
                throw new MyException(10001, $e->getMessage());
            }
        }

        Db::commit();
        return $res;
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
