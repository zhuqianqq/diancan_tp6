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
use app\model\CompanyRegister;

/**
 * 订餐设置
 * Class DingcanSysconfig
 * @package app\model
 * @author  2066362155@qq.com
 */
class DingcanSysconfig extends BaseModel
{
    //时间字段显示格式
    protected $dateFormat = 'Y-m-d H:i:s';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'datetime';

    use ModelTrait;


    //获取钉钉对应公司的部门id
    public static function isCompleteSysConf($corpId)
    {
        $company_id = CompanyRegister::where('corpid',$corpId)->value('company_id');

        return self::where('company_id',$company_id)->count();
    }

}