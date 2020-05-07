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
 * 订餐设置
 * Class Food
 * @package app\model
 * @author  2066362155@qq.com
 */
class DingcanSysconfig extends Model
{
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';

    use ModelTrait;

}