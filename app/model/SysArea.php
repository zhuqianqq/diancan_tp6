<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/5
 * Time: 12:04
 */

namespace app\model;


use app\traits\ModelTrait;
use app\model\BaseModel;
/**
 * 管理员
 * Class SysArea
 * @package app\model
 * @author  2066362155@qq.com
 */
class SysArea extends BaseModel
{
   
	//获取省、市、区的code对应的中文名
    public static function getAreaName($code)
    {
        return self::where('area_id',$code)->value('area_name');
    }
}