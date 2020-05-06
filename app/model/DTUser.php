<?php
declare (strict_types=1);

namespace app\model;

use think\Model;
use app\traits\ModelTrait;

/**
 * 用户
 * Class User
 * @package app\model
 * @author  2066362155@qq.com
 */
class DTUser extends Model
{

    //据输出显示的属性
    public static $showField = ['id', 'openid', 'phone', 'username', 'is_enabled', 'nickname', 'img', 'sex', 'balance', 'birth', 'descript', 'money', 'create_time', 'reg_ip', 'login_ip', 'login_time', 'update_time'];

    //查询类型转换, 与Model 中的type类型转化功能相同，只是新增字符串类型
    protected $selectType = [
        'id' => 'string',
    ];

    use ModelTrait;
}

