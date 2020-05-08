<?php
declare (strict_types=1);

namespace app\controller\api;
require_once '../extend/dingtalk_isv_php_sdk/api/Auth.php';
require_once '../extend/dingtalk_isv_php_sdk/api/ISVService.php';
require_once '../extend/dingtalk_isv_php_sdk/api/User.php';
require_once '../extend/dingtalk_isv_php_sdk/api/Department.php';

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\model\DTCompany;
use app\model\DTUser;
use app\model\DTDepartment;
use think\facade\Db;


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

        //判定设备型号
        $request = request();
        $user_info->isMobile = $request->isMobile();
        
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

           $userInfo = $DTUserModel->where('platform_staffid',$userid)->find();

           return json_ok($userInfo);

       }else{
           //管理员 维护其登录时间login_time login_ip字段
           $DTUserModel->updateAdminInfo($corpId,$userid);
            
       }
       //老用户查询后返回数据库结果
       return json_ok($isReg);

    }


     /**
     * @Route("DTGetDepartment")
     */

    //获取钉钉企业部门信息
    public function DTGetDepartment()
    {
       $corpId = input('corpId','ding076b713cc1eff17735c2f4657eb6378f');

       if(!$corpId){
              return  json_error(20002);
       }

       $company_id = Db::table("dc_company_register")
        ->where('corpid',$corpId)
        ->value('company_id');

       if(!$company_id){
              return  json_error(20050);
       }

       $DTDepartmentModel = new DTDepartment;
       $isReg = $DTDepartmentModel->where('company_id',$company_id)->find();

       if(!$isReg){
             try {
               //钉钉接口获取部门信息
               $Department = new \Department();
               $isvCorpAccessToken = $this->getIsvCorpAccessToken($corpId);
               $departmentList = $Department->listDept($isvCorpAccessToken);
               foreach ($departmentList->department as $k => $v) {
                    $departmentDetail = $Department->detailDept($isvCorpAccessToken,$v->id);
                    $departmentList->department[$k]->detail = $departmentDetail;
               }
               //部门信息加入数据库
               $DTDepartmentModel->registerDepartment($departmentList,$company_id);
             } catch (\Exception $e) {
                throw new \app\MyException(20060);
             }
          
       }

       return json_ok();
    }

    /**
     * @Route("test")
     */
    public function test()
    {
         //获取公司信息
        // $DTCompanyModel = new DTCompany;
        // dd($DTCompanyModel);
       return json_ok(isWorkDay()); 
       //return json_ok(input('param.'));
        // $User = new \User();
        // $user_info = $User->getUserInfo();
        // dd($user_info);
    }


}
