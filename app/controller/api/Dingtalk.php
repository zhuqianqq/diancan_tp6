<?php
declare (strict_types=1);

namespace app\controller\api;
require_once '../extend/dingtalk_isv_php_sdk/api/Auth.php';

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;

/**
 * 钉钉接口
 * Class Dingtalk
 * @package app\controller\api
 * @author  2066362155@qq.com
 * @Group("api/Dingtalk")
 */
class Dingtalk extends Base
{
    /**
     * @Route("index", method="GET")
     */
    public function index()
    {
        echo 'Dingtalk';
        $dingtalk_auth = new \Auth();
        dd($dingtalk_auth);
    }


    /**
     * @Route("activateSuite", method="GET")
     */
    //激活套件
    public function activateSuite()
    {
        require_once '../extend/dingtalk_isv_php_sdk/receive.php';
    }
}
