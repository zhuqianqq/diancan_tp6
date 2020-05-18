<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 注册餐馆
 * Class Order
 * @package app\validate
 * @author  2066362155@qq.com
 * @date 2019-11-27
 */
class Order extends Validate
{
    //验证规则
    protected $rule = [
        'staffid'   => ['require','integer'],
        'eatery_id'   => ['require','integer'],
        'report_amount'   => ['require','checkMoney'],
        'report_num' => ['require','integer'],
        'food_name' => ['require','integer'],
    ];

    //提示信息
    protected $message = [
        'staffid.require'   => '员工ID必须',
        'staffid.integer'   => '员工ID必须为正整数',
        'eatery_id.require'   => '餐馆ID必须',
        'eatery_id.integer'   => '餐馆ID必须为正整数',
        'report_num'    => '报餐总数量必须',
        'food_name'    => '报餐总数量必须',
        'report_amount.require'    => '报餐价格必须',
    ];

    //验证场景
    protected $scene = [
        'save' => ['eatery_id','report_amount','eatery_name'],
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
