<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 注册餐馆
 * Class Food
 * @package app\validate
 * @author  2066362155@qq.com
 * @date 2019-11-27
 */
class Food extends Validate
{
    //验证规则
    protected $rule = [
        'food_id' => ['require','integer'],
        'eatery_id'   => ['require','integer'],
    ];

    //提示信息
    protected $message = [
        'eatery_id.require'   => '餐馆ID必须',
        'eatery_id.integer'   => '餐馆ID必须为正整数',
        'food_id'    => '菜品ID必须',
    ];

    //验证场景
    protected $scene = [
        'save' => ['eatery_id','food_name','price'],
        'delete' => ['food_id'],
    ];

    public function checkMoney($value, $rule, array $data = [])
    {
        $money_reg = '/(^[0-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
        if(!preg_match($money_reg, $value)){
            return '价格格式错误';
        }else
            return true;
    }

}
