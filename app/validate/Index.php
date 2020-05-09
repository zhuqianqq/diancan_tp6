<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 注册餐馆
 * Class Index
 * @package app\validate
 * @author  2066362155@qq.com
 * @date 2019-11-27
 */
class Index extends Validate
{
    //验证规则
    protected $rule = [
        'end_time_type' => ['require','integer'],
        'dc_date'   => ['require','integer'],
        'food_info'   => ['require'],
    ];

    //提示信息
    protected $message = [
        'end_time_type.require'   => '订餐截至时间类型必须',
        'eatery_id.integer'   => '订餐截至时间类型必须为正整数',
        'dc_date.require'    => '订餐日必须选择',
        'food_info'    => '菜品信息必须填写',
    ];

    //验证场景
    protected $scene = [
        'save' => ['end_time_type','dc_date','food_info'],
    ];


}
