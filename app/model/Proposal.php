<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/5
 * Time: 12:04
 */
namespace app\model;

use app\traits\ModelTrait;
use app\model\Food;

/**
 * 意见反馈
 * Class Proposal
 * @package app\model
 * @author  2066362155@qq.com
 */
class Proposal extends BaseModel
{
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';
    //只读字段，不允许被更改
    protected $readonly = [];
    //数据输出隐藏的属性
    protected $hidden = [];

    use ModelTrait;

}