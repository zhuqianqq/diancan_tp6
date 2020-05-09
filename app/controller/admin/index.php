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
use app\service\Index AS I;
use think\annotation\route\Group;
use think\annotation\Route;
use app\validate\Index AS VI;
use think\annotation\route\Validate;


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
     * @Route("setting", method="GET")
     * @Validate(VI::class,scene="save",batch="true")
     */
    public function orderSetting()
    {
        $result = I::setting();
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
        $data['user_id'] = input('post.user_id','','int');
        $data['contact'] = input('post.contact','','string');
        $data['mobile'] = input('post.mobile','','string');
        $data['province'] = input('post.province','','string');
        $data['city'] = input('post.city','','string');
        $data['address'] = input('post.address','','string');
        $result = I::companySetting($data);
        return json_ok($result);
    }

    /**
     * 最近订餐
     * @Route("recentlyOrder", method="GET")
     */
    public function recentlyOrdering()
    {

    }

}