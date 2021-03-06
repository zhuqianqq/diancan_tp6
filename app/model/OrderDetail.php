<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/5
 * Time: 12:04
 */
namespace app\model;

use app\traits\ModelTrait;

/**
 * 订单详情
 * Class OrderDetail
 * @package app\model
 * @author  2066362155@qq.com
 */
class OrderDetail extends BaseModel
{
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';

    public static $_table = 'dc_order_detail';
    protected $table = 'dc_order_detail';

    use ModelTrait;

}