<?php
declare (strict_types=1);

namespace app\controller\api;
require_once '../extend/dingtalk_isv_php_sdk/api/Auth.php';
require_once '../extend/dingtalk_isv_php_sdk/api/ISVService.php';

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
     * @Route("activateSuite")
     */
    //激活套件
    public function activateSuite()
    {
        require_once '../extend/dingtalk_isv_php_sdk/receive.php';
    }

    /**
     * @Route("getSuiteAccessToken", method="GET")
     */
    //激活套件
    public function getSuiteAccessToken()
    {
        echo 'ISVService';
        $ISVService = new \ISVService();
        //获取第三方应用凭证
        $suiteAccessToken = $ISVService->getSuiteAccessToken('10530003');
        echo $suiteAccessToken;die;
        //获取企业授权凭证
        $CorpId = 'ding076b713cc1eff17735c2f4657eb6378f';
        $isvCorpAccessToken = $ISVService->getIsvCorpAccessToken($suiteAccessToken,$CorpId,'10530003');
        dd($isvCorpAccessToken);
    }

}
