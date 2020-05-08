<?php
declare (strict_types=1);
namespace app\service;

use app\model\Food as F;
use app\MyException;
use app\traits\ServiceTrait;
use app\service\Eatery;

/**
 * 菜品
 * Class Food
 * @package app\service
 * @author  2066362155@qq.com
 */
class Food
{

    //仓库，带命名空间
    public static $repository = 'app\repository\EateryRegisterRepository';

    use ServiceTrait;

    /**
     * 菜品列表
     */
    public static function getInfo()
    {
        $result = Eatery::getlist();
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
        if ($food_id==0) {//新增
            try {
                $foodM = new F;
                $foodM->save($data);
                return [];
            }catch (\Exception $e){
                throw new MyException(14001, $e->getMessage());
            }
        } else { //编辑
            $oneFood = F::find($food_id);
            if (!$oneFood) {
                throw new MyException(14002);
            }
            try {
                $oneFood->save($data);
                return [];
            }catch (\Exception $e){
                throw new MyException(14001, $e->getMessage());
            }
        }
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
