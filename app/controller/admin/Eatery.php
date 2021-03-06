<?php
/**
 * Created by PhpStorm.
 * User: 92163
 * Date: 2020/5/2
 * Time: 18:48
 */
namespace app\controller\admin;

use app\controller\admin\Base;
use app\service\EateryService as SE;
use think\annotation\Route;
use think\annotation\route\Group;

/**
 * 餐馆接口
 * Class Eatery
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/Eatery")
 */
class Eatery extends Base {
	/**
	 * 餐馆管理列表
	 * @Route("lists", method="GET")
	 */
	public function lists()
    {
		$result = SE::getlists();
		return json_ok($result);
	}

	/**
	 * 最近订餐
	 * @Route("recentlyOrder")
	 */
	public function recentlyOrdering()
    {
		$result = SE::getRecentlyOrders();
		return json_ok($result);
	}

}