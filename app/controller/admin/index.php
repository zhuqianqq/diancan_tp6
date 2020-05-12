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
use app\model\CompanyRegister;
use app\traits\ControllerTrait;
use app\service\Index AS I;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Index AS VI;
use think\annotation\route\Validate;
use app\service\Eatery as SE;

/**
 * 订餐后台首页
 * Class index
 * @package app\controller\admin
 * @author  2066362155@qq.com
 * @Group("admin/index")
 */
class index extends Base
{
    /**
     * 订餐设置
     * @Route("orderSetting", method="POST")
     * @Validate(VI::class,scene="save",batch="true")
     */
    public function orderSetting()
    {
        $data = input('param.');
        $result = I::setting($data);
        return json_ok($result);
    }

    /**
     * 首页是否系统设置
     * @Route("isSetted", method="GET")
     */
    public function isSetted()
    {
        $userId = input('get.user_id','','int');
        $result = I::isSet($userId);
        return json_ok($result);
    }

    /**
     * 公司设置
     * @Route("companySetting", method="POST")
     */
    public function companySetting()
    {
        $data['user_id'] = input('user_id','', 'int');
        $data['contact'] = input('contact','', 'string');
        $data['mobile'] = input('mobile','', 'string');
        $data['province'] = input('province','', 'string');
        $data['city'] = input('city','', 'string');
        $data['district'] = input('district','', 'string');
        $data['address'] = input('address','', 'string');
        $result = I::companySetting($data);
        return json_ok($result);
    }


    /**
     * 公司设置
     * @Route("getCompanySetting")
     */
    public function getCompanySetting()
    {
        $user_id = input('user_id','','int');
        if (!$user_id) {
            return json_error(10002);
        }
        $result = I::getCompanySetting($user_id);
        return json_ok($result);
    }


    /**
     * 最近订餐
     * @Route("recentlyOrder", method="GET")
     */
    public function recentlyOrdering()
    {
        $result = SE::getRecentlyOrders();
        return json_ok($result);
    }

}