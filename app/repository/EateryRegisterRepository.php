<?php
declare (strict_types=1);
namespace app\repository;

use app\traits\RepositoryTrait;

/**
 * 餐馆注册
 * Class EateryRegisterRepository
 * @package app\repository
 * @author  2066362155@qq.com
 */
class EateryRegisterRepository
{
    //模型，带命名空间
    public static $model = 'app\model\EateryRegister';
    //模型主键
    public static $pk = 'eatery_id';

    use RepositoryTrait;

}
