<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/5
 * Time: 12:04
 */

namespace app\model;

use think\Model;
use app\traits\ModelTrait;

/**
 *公司注册
 * Class CompanyRegister
 * @package app\model
 * @author  2066362155@qq.com
 */
class CompanyRegister extends BaseModel
{
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';
    //只读字段，不允许被更改
    protected $readonly = [];
    //数据输出隐藏的属性
    protected $hidden = [];
    //关闭自动写入update_time
    protected $updateTime = false;

    public  static $_table  = "dc_company_register";

    use ModelTrait;
}