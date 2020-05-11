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
 * 管理员
 * Class EateryRegister
 * @package app\model
 * @author  2066362155@qq.com
 */
class EateryRegister extends Model
{
    protected $pk = 'eatery_id';
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';
    //只读字段，不允许被更改
    protected $readonly = [];
    //数据输出隐藏的属性
    protected $hidden = ['password'];

    //据输出显示的属性
    public static $showField = ['eatery_id', 'eatery_name', 'contacts', 'mobile', 'proive', 'city', 'district', 'address', 'eat_type', 'create_time'];

    //查询类型转换, 与Model 中的type类型转化功能相同，只是新增字符串类型
    protected $selectType = [
        'eat_type'        => 'string',
    ];

    use ModelTrait;

    public function food()
    {
        return $this->hasMany(Food::class,'eatery_id');
    }

    public static function getEateryName($eatery_id)
    {
        return self::where('eatery_id',$eatery_id)->value('eatery_name');
    }
}