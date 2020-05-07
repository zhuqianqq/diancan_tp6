<?php
declare (strict_types=1);
namespace app\service;

use app\model\Food as F;
use app\MyException;
use app\traits\ServiceTrait;

/**
 * 订餐设置
 * Class Food
 * @package app\service
 * @author  2066362155@qq.com
 */
class DingcanSysconfig
{
    use ServiceTrait;

    /**
     * 更新或者创建菜品
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function setting()
    {
        try {
            $foodM = new F;
            $foodM->save($data);
            return [];
        }catch (\Exception $e){
            throw new MyException(14001, $e->getMessage());
        }
    }

}
