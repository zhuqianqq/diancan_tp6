<?php
declare (strict_types=1);

namespace app\controller\api;

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\model\DTCompany;
use app\model\DTUser;
use app\model\DTDepartment;
use app\model\CompanyStaff;
use think\facade\Db;
use think\facade\Log;
use app\model\CompanyAdmin;
use app\util\AccessKeyHelper;

$root_path = app()->getRootPath();

require_once $root_path . 'extend/dingtalk_isv_php_sdk/api/Auth.php';
require_once $root_path . 'extend/dingtalk_isv_php_sdk/api/ISVService.php';
require_once $root_path . 'extend/dingtalk_isv_php_sdk/api/User.php';
require_once $root_path . 'extend/dingtalk_isv_php_sdk/api/Department.php';

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

        $suiteAccessToken = $this->ISVService->getSuiteAccessToken('10530003');

        return $suiteAccessToken;

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

    //获取订单公司授权信息 cache数据
    public function getIsvCorpAuthInfo($corpId)
    {

        $key = 'corpAuthInfo_'.$corpId;

        return json_decode($this->Auth->cache->getAuthInfo($key),true);
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

       $DTUserModel = new DTUser;
       $DTDepartmentModel = new DTDepartment;
       
       $isReg = $DTUserModel->where('platform_staffid',$userid)->find();

       if(!$isReg){
           //新用户 注册逻辑
           //获取企业授权凭证
           $isvCorpAccessToken = $this->getIsvCorpAccessToken($corpId);

           $User = new \User();

           $user_info = $User->get($isvCorpAccessToken,$userid);
           
           $DTUserModel->registerStaff($user_info,$corpId);

           //员工信息
           $userInfo = $DTUserModel->where('platform_staffid',$userid)->find();
           //统一userid字段
           $userInfo->userid = $userInfo->staffid;

           //返回生成的access_key
           $userInfo['access_key'] = AccessKeyHelper::generateAccessKey($userInfo->userid);
           
           //判断该用户数据库是否有部门信息
           $userInfo['hasDepartment'] = $DTDepartmentModel->where('company_id',$userInfo['company_id'])->count();

           return json_ok($userInfo);

       }else{
           //若为管理员 维护其登录时间login_time login_ip字段 同时把返回前端信息换成管理员数据库信息
           $isAdmin = CompanyAdmin::isAdmin($corpId,$userid);
           if($isAdmin){
                CompanyAdmin::updateAdminInfo($corpId,$userid);
           }

           //员工身份 统一userid字段
           $isReg->userid = $isReg->staffid;

           //判断redis缓存是否有access_key 并且 未过期
           $isReg['access_key'] = AccessKeyHelper::getAccessKey($isReg->userid);
           if(!$isReg['access_key']){
              //生成的access_key
              $isReg['access_key'] = AccessKeyHelper::generateAccessKey($isReg->userid); 
           }
           

           //判断该用户数据库是否有部门信息
           $isReg['hasDepartment'] = $DTDepartmentModel->where('company_id',$isReg['company_id'])->count();
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
       $corpId = input('corpId','');

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
     * @Route("sendMessage")
     */
    //发送订餐消息（钉钉工作消息类型）
    public function sendMessage($corpId)
    {
        //$corpId = input('corpId','ding856732f3dcf58a39a1320dcb25e91351');

        if(!$corpId){
              return  json_error(20002);
        }

        $isvCorpAuthInfo = $this->getIsvCorpAuthInfo($corpId);
        $agentid = $isvCorpAuthInfo['agent'][0]['agentid'] ?? '';
        if(!$agentid){
            return  json_error(20800);
        }
     
        require_once app()->getRootPath() . 'extend/dingtalk_isv_php_sdk/api/Message.php';
        $Message = new \Message();
        $isvCorpAccessToken = $this->getIsvCorpAccessToken($corpId);

        $opt = $sub_data = [];
        $opt['agent_id'] = $agentid;

        //动作卡方式
        // $opt['msg']['msgtype'] = 'action_card';
        // $sub_data['title'] = "天天点餐";
        // $sub_data['markdown'] = "订餐开始喽！请及时进入小程序订餐";
        // $sub_data['single_title'] = "立即订餐";
        // $sub_data['single_url'] = "http://www.baidu.com";
        // $opt['msg']['action_card'] = $sub_data;

        //文本方式
        $opt['msg']['msgtype'] = 'text';
        $opt['msg']['text'] = ['content'=>"订餐开始喽！请及时进入钉钉工作台->应用->订餐应用小程序进行订餐"];

        $userid_list_arr = CompanyStaff::getDingdingUserIds($corpId);
        if(!$userid_list_arr){
            return  json_error(20900);
        } 

        $userid_list = implode(',', $userid_list_arr);

        $opt['userid_list'] = $userid_list;

        $res = $Message->corpConversation($isvCorpAccessToken,$opt);
      
        if($res->errcode == 0 ){
            $msg = "发送订餐消息成功：对应公司corpId:{$corpId},agentid:{$agentid} ,钉钉接口返回： ". json_encode($res,JSON_UNESCAPED_UNICODE);
            Log::info($msg);
            return $corpId;
        }else{

            $msg = "发送订餐消息失败：对应公司corpId:{$corpId},agentid:{$agentid} ,钉钉接口返回： ". json_encode($res,JSON_UNESCAPED_UNICODE);
            Log::error($msg);
            Log::error('opt:' . json_encode($opt,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }



    /**
     * @Route("test")
     */
    public function test()
    {

        //$list = Db::table("dc_company_staff")->where('staffid',1)->select();
        //echo Db::table("dc_company_staff")->getLastSql();die;
        //echo app()->getRootPath();die;
        echo  \think\facade\Env::get("APP_ENV");die;
        $token = setH5token(19,4);
        echo $token;die;
       return json_ok(isWorkDayJs()); 

    }


    /**
     * @Route("area_tree")
     */
    //生成省市区js文件
    public function area_tree()
    {

       $TreeUtil = new \app\util\TreeUtil;
       //数据库中香港 澳门 台湾三区不返回
       $list = Db::table("dc_sys_area")->whereRaw('area_id Not IN (710000,810000,820000)')->select()->toArray();
       $content = json_encode($TreeUtil->list_to_tree($list,0,'area_id','parent_id'));
       $log_name = 'sys_area.js';
       $log_file = app()->getRootPath() . "public/js/" . ltrim($log_name, "/"); //保存在runtime/log/目录下
       $path = dirname($log_file);
       !is_dir($path) && @mkdir($path, 0755, true); //创建目录
       @file_put_contents($log_file, $content);
    }


}
