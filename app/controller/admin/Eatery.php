<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/2
 * Time: 18:48
 */

namespace app\controller\admin;

use app\controller\admin\Base;
use app\model\CompanyAdmin;
use app\traits\ControllerTrait;
use app\service\Eatery as SE;
use think\annotation\route\Group;
use think\annotation\Route;


/**
 * 餐馆接口
 * Class Eatery
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/Eatery")
 */
class Eatery extends Base
{
    /**
     * 餐馆管理列表
     * @Route("lists", method="GET")
     */
    public function lists()
    {
        $result = SE::getlists();
        return $result;
    }

    /**
     * 最近订餐
     * @Route("recentlyOrder", method="GET")
     */
    public function recentlyOrdering()
    {

    }

}