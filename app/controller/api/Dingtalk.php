<?php
declare (strict_types=1);

namespace app\controller\api;
require_once '../extend/dingtalk_isv_php_sdk/api/Auth.php';
require_once '../extend/dingtalk_isv_php_sdk/api/ISVService.php';
require_once '../extend/dingtalk_isv_php_sdk/api/User.php';

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\model\DTCompany;
use app\model\DTUser;

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
    //钉钉登录首页
    public function index()
    {   
        $corpId = input('corpId','');
        $code = input('code','');
        if(!$corpId && !$code){
            return json_error(20001);
        }
        //获取企业授权凭证
        $isvCorpAccessToken = $this->getIsvCorpAccessToken($corpId);

        $User = new \User();
        $user_info = $User->getUserInfo($isvCorpAccessToken,$code);

        return json_ok($user_info);
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
     * @Route("getSuiteAccessToken")
     */
    //获取isv套件应用凭证
     public function getSuiteAccessToken()
    {
        //echo 'ISVService';
        $suiteAccessToken = $this->ISVService->getSuiteAccessToken('10530003');

        return $suiteAccessToken;

        //获取js_ticket
        //$js_ticket = $this->Auth->getTicket($CorpId,$isvCorpAccessToken);

        //dd($js_ticket);
    }

    //isv应用免登陆的公司AccessToken
    public function getIsvCorpAccessToken($corpId)
    {
        $key = 'dingding_corp_info_'.$corpId;

        $CorpInfo = json_decode($this->Auth->cache->getCorpInfo($key),true);

        foreach ($CorpInfo as $k => $v) {
           $CorpId = $k;
           $permanent_code = $v['permanent_code'];
        }

        $suiteAccessToken = $this->getSuiteAccessToken();
        //获取企业授权凭证
        $isvCorpAccessToken = $this->ISVService->getIsvCorpAccessToken($suiteAccessToken,$CorpId,$permanent_code);

        return $isvCorpAccessToken;

    }



    // public function getSuiteAccessToken()
    // {
    //     //获取套件第三方应用凭证
    //     $suiteAccessToken = $this->ISVService->getSuiteAccessToken('10530003');

    //     $CorpInfo = json_decode($this->Auth->cache->getCorpInfo(),true);
        
    //     foreach ($CorpInfo as $k => $v) {
    //        $CorpId = $k;
    //        $permanent_code = $v['permanent_code'];
    //     }

    //     //获取公司信息
    //     $DTCompanyModel = new DTCompany;
    //     $isCompanyRegister = $DTCompanyModel->find($CorpId);
    //     if(!$isCompanyRegister){
    //        $CompanyAuthInfo = $this->getCompanyAuthInfo($suiteAccessToken,$CorpId,$permanent_code);
    //        if($CompanyAuthInfo){
    //         echo 123;die;
    //             //$DTCompanyModel->register();
    //        }
            
    //     }
      
    //     echo '<pre>';var_dump($authInfo);die;

    //     $AuthCorpInfo = $this->Auth->cache->getAuthInfo("corpAuthInfo_".$CorpId);
    //     return $AuthCorpInfo;
        
    //     //获取企业授权凭证
    //     $isvCorpAccessToken = $this->ISVService->getIsvCorpAccessToken($suiteAccessToken,$CorpId,$permanent_code);
    //     return $isvCorpAccessToken;
    //     //获取js_ticket
    //     //$js_ticket = $this->Auth->getTicket($CorpId,$isvCorpAccessToken);

    //     //dd($js_ticket);
    // }


    // //获取公司信息
    // public function getCompanyAuthInfo($suiteAccessToken,$CorpId,$permanent_code)
    // {
    //      return $this->Auth->http->post("/service/get_auth_info",
    //                 array(
    //                     "suite_access_token" => $suiteAccessToken
    //                 ),
    //                 json_encode(array(
    //                     "suite_key" => SUITE_KEY,
    //                     "auth_corpid" => $CorpId,
    //                     "permanent_code" => $permanent_code
    //                 )));
    // }

    /**
     * @Route("DTGetUserInfo")
     */

    //获取钉钉员工详细信息
    public function DTGetUserInfo()
    {
       $userid = input('userid','');
       $corpId = input('corpId','');


       if(!$userid || !$corpId){
         return  json_error(20005);
       }

       //获取企业授权凭证
       $DTUserModel = new DTUser;
     
       $isReg = $DTUserModel->where('platform_staffid',$userid)->find();

       if(!$isReg){
           //新用户 注册逻辑
           $isvCorpAccessToken = $this->getIsvCorpAccessToken($corpId);

           $User = new \User();

           $user_info = $User->get($isvCorpAccessToken,$userid);
   
           $res = $DTUserModel->registerStaff($user_info,$corpId);

           if($res){

              $userInfo = $DTUserModel->where('platform_staffid',$userid)->find();
              return json_ok($userInfo);

           }else{

              return  json_error(20020);

           }
       }
       //老用户查询后返回数据库结果
       return json_ok($isReg);

    }

    /**
     * @Route("test")
     */
    public function test()
    {
         //获取公司信息
        $DTCompanyModel = new DTCompany;
        dd($DTCompanyModel);

       //return json_ok(input('param.'));
        // $User = new \User();
        // $user_info = $User->getUserInfo();
        // dd($user_info);
    }


}
