<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 注册餐馆
 * Class EateryRegister
 * @package app\validate
 * @author  2066362155@qq.com
 * @date 2019-11-27
 */
class EateryRegister extends Validate
{
    //验证规则
    protected $rule = [
        'user_id'   => ['require'],
        'eatery_id'   => ['require','number'],
        'eatery_name' => ['require'],
        'contacts' => ['require'],
        'proive' => ['require'],
        'city' => ['require'],
        'district' => ['require'],
        'address' => ['require'],
        'eat_type' => ['require'],
        'mobile'    => ['require','regex' => '/1[3458]{1}\d{9}$/'],
    ];

    //提示信息
    protected $message = [
        'user_id'   => '用户ID必须',
        'eatery_id'   => '餐馆ID必须',
        'eatery_name.require'   => '餐馆名称必须',
        'username.unique'    => '餐馆名称已存在',
        'contacts'           => '联系人必须',
        'city'           => '请选择市',
        'district'        => '请选择区',
        'address'              => '请填写详细地址',
        'eat_type'     => '餐时供用必须',
        'mobile.regex'        => '手机格式错误',
    ];

    //验证场景
    protected $scene = [
        'save' => [ 'user_id','type','eatery_name', 'contacts','proive','city','district','address','eat_type','mobile'],
        'delete' => ['user_id','eatery_id']
    ];

}
