<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 订餐设置
 * Class DingcanSysconfig
 * @package app\validate
 * @author  2066362155@qq.com
 * @date 2019-11-27
 */
class DingcanSysconfig extends Validate
{
    //验证规则
    protected $rule = [
        'company_id'   => ['require','integer'],
        'end_time_type' => ['require'],
        'dc_date' => ['require'],
        'news_time_type' => ['require'],
    ];

    //提示信息
    protected $message = [
        'company_id.require'   => '用户ID必须',
        'company_id.integer'   => '用户ID必须事正整数',
        'end_time_type'   => '订餐截止时间必须',
        'dc_date'   => '订餐日必须选择',
        'news_time_type'    => '自动消息提醒类型必须选择',
    ];

    //验证场景
   /* protected $scene = [
        'save' => [ 'user_id','type','eatery_name', 'contacts','proive','city','district','address','eat_type','mobile'],
    ];*/

}
