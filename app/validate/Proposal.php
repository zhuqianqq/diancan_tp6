<?php
declare (strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 意见反馈
 * Class Proposal
 * @package app\validate
 * @author  2066362155@qq.com
 * @date 2019-11-27
 */
class Proposal extends Validate
{
    //验证规则
    protected $rule = [
        'mobile' => ['require','mobile'],
        'content'   => ['require'],
    ];

    //提示信息
    protected $message = [
        'mobile.require'   => '手机号必须',
        'mobile.mobile'   => '手机号格式不正确',
        'content'    => '反馈内容必须',
    ];

    //验证场景
    protected $scene = [
        'save' => ['mobile','content'],
    ];

}
