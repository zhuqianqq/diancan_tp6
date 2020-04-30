<?php
declare (strict_types=1);

namespace app\controller\api;
require_once '../extend/dingtalk_isv_php_sdk/api/Auth.php';
require_once '../extend/dingtalk_isv_php_sdk/api/ISVService.php';
require_once '../extend/dingtalk_isv_php_sdk/api/User.php';

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

    public $Auth;
    public $ISVService;
    public function __construct() {
        $this->Auth = new \Auth();
        $this->ISVService = new \ISVService();
    }


    /**
     * @Route("index", method="GET")
     */
    public function index()
    {
        echo 'Dingtalk';
        dd($this->Auth);
    }


    /**
     * @Route("activateSuite")
     */
    //激活套件
    public function activateSuite()
    {
        require_once '../extend/dingtalk_isv_php_sdk/receive.php';
    }


    public function getSuiteAccessToken()
    {
        echo 'ISVService';
        //获取第三方应用凭证
        $suiteAccessToken = $this->ISVService->getSuiteAccessToken('10530003');

        $CorpInfo = json_decode($this->Auth->cache->getCorpInfo(),true);

        foreach ($CorpInfo as $k => $v) {
           $CorpId = $k;
           $permanent_code = $v['permanent_code'];
        }
        
        //获取企业授权凭证
        $isvCorpAccessToken = $this->ISVService->getIsvCorpAccessToken($suiteAccessToken,$CorpId,$permanent_code);
        //获取js_ticket
        $js_ticket = $this->Auth->getTicket($CorpId,$isvCorpAccessToken);

        dd($js_ticket);
    }

    /**
     * @Route("userInfo")
     */
    public function getUserInfo()
    {

       return json_ok(input('param.'));
        // $User = new \User();
        // $user_info = $User->getUserInfo();
        // dd($user_info);
    }

}
