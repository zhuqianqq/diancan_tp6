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
           
           $DTUserModel->registerStaff($user_info,$corpId);

           if($user_info->isAdmin === true){
                //管理员身份
                $userInfo = $DTUserModel->isAdmin($corpId,$userid);

           }else{
                //员工身份 
                $userInfo = $DTUserModel->where('platform_staffid',$userid)->find();
                //统一userid字段
                $userInfo->userid = $userInfo->staffid;
           }

           return json_ok($userInfo);

       }else{
           //若为管理员 维护其登录时间login_time login_ip字段 同时把返回前端信息换成管理员数据库信息
           $isAdmin = $DTUserModel->isAdmin($corpId,$userid);
           if($isAdmin){
                $DTUserModel->updateAdminInfo($corpId,$userid);
                $isReg = $isAdmin;
           }else{
             //员工身份 统一userid字段
             $isReg->userid = $isReg->staffid;
           }
            
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
    public function sendMessage()
    {
        $corpId = input('corpId','dingfecc037dae3b317624f2f5cc6abecb85');

        if(!$corpId){
              return  json_error(20002);
        }

        require_once '../extend/dingtalk_isv_php_sdk/api/Message.php';
        $Message = new \Message();
        $isvCorpAccessToken = $this->getIsvCorpAccessToken($corpId);

        $opt = $sub_data = [];
        $opt['agent_id'] = '759850263';

        $opt['msg']['msgtype'] = 'action_card';
        $sub_data['btn_json_list'] = ['action_url'=>"http://www.baidu.com",'title'=>"kevin测试"];
        $sub_data['title'] = "天天点餐";
        $sub_data['btn_orientation'] = "1";
        $sub_data['single_title'] = "立即订餐";
        $opt['msg']['action_card'] = $sub_data;

        $opt['dept_id_list'] = '1';
    
       
        $res = $Message->corpConversation($isvCorpAccessToken,$opt);
            //return json_ok($opt); 
        dd($res);

//         {
//     "agent_id":"759850263",
//     "msg":{
//         "msgtype":"action_card",
//         "action_card":{
//             "btn_json_list":{
//                 "action_url":"http://www.baidu.com",
//                 "title":"kevin测试"
//             },
//             "title":"天天点餐",
//             "btn_orientation":"1",
//             "single_title":"立即订餐"
//         }
//     },
//     "dept_id_list":"1"
// }

        echo 123;die;
    }



    /**
     * @Route("test")
     */
    public function test()
    {

        $list = Db::table("dc_company_staff")->where('staffid',1)->select();
        echo Db::table("dc_company_staff")->getLastSql();die;
       return json_ok(isWorkDay()); 

    }


    /**
     * @Route("area_tree")
     */
    //生成省市区js文件
    public function area_tree()
    {

       ini_set('max_execution_time', '0');
       $TreeUtil = new \app\util\TreeUtil;
       $list = Db::table("dc_sys_area")->select()->toArray();
       $content = json_encode($TreeUtil->list_to_tree($list,0,'area_id','parent_id'));
       $log_name = 'sys_area.js';
       $log_file = app()->getRuntimePath() . "log/" . ltrim($log_name, "/"); //保存在runtime/log/目录下
       $path = dirname($log_file);
       !is_dir($path) && @mkdir($path, 0755, true); //创建目录
       @file_put_contents($log_file, $content, FILE_APPEND);
    }


}
