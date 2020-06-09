<?php
declare (strict_types=1);

namespace app\controller\api;

use app\controller\api\Base;
use app\model\BaseModel;
use app\model\CompanyRegister;
use app\MyException;
use think\annotation\route\Group;
use think\annotation\Route;
use app\model\DTCompany;
use app\model\DTUser;
use app\model\DTDepartment;
use app\model\CompanyStaff;
use app\model\DingcanSysconfig;
use think\facade\Cache;
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
    //钉钉登录首页 （小程序登录接口）
    public function index()
    {
        $CorpId = input('corpId','');
        $code = input('code','');
        if(!$CorpId && !$code){
            return json_error(20001);
        }

        //获取授权信息
        $authData = self::getAuthOrTicketInfo($CorpId, 4);
        if (empty($authData)) {
            return json_error(10004);
        }
        $data = json_decode($authData['biz_data'], true);

        //获取授权信息
        $isvCorpAccessToken = $this->getIsvCorpAccessToken($CorpId);
        $User = new \User();
        $user_info = $User->getUserInfo($isvCorpAccessToken,$code);

        return json_ok($user_info);
    }



    /**
     * @Route("backendIndex", method="GET")
     */
    //钉钉登录首页 （后台登录接口）
    public function backendIndex()
    {
        $CorpId = input('corpId','');
        $code = input('code','');
        if(!$CorpId && !$code){
            return json_error(20001);
        }

        //获取授权信息
        $authData = self::getAuthOrTicketInfo($CorpId, 4);
        if (empty($authData)) {
            return json_error(10004);
        }
        $data = json_decode($authData['biz_data'], true);

        //获取所有已经存在的公司
        $oneCompany =  Db::connect('mysql')
            ->table('dc_company_register')
            ->where('corpid =:corpid', ['corpid' => $CorpId])
            ->find();

        BaseModel::beginTrans();
        if (!$oneCompany) {
            try {
                self::registerCompany($data['auth_corp_info'], $data['permanent_code']);
                self::DTGetDepartment($CorpId);
                BaseModel::commitTrans();
            } catch (\Exception $e) {
                BaseModel::rollbackTrans();
                throw new MyException(20080);
            }
        }

        //获取授权信息
        $isvCorpAccessToken = $this->getIsvCorpAccessToken($CorpId);
        $User = new \User();
        $user_info = $User->getUserInfo($isvCorpAccessToken,$code);

        //判定设备型号
        $request = request();
        $user_info->isMobile = $request->isMobile(); 

        return json_ok($user_info);
    }

    /**
     * 获取钉钉云推送信息
     * @param $CorpId
     * @param $type
     */
    public static function getAuthOrTicketInfo($CorpId, $type)
    {

        $info = Db::connect('yun_push')
            ->table('open_sync_biz_data')
            ->order('id desc')
            ->where('corp_id =:corp_id and biz_type=:biz_type', ['corp_id' => $CorpId, 'biz_type' => $type])
            ->find();
        return $info;
    }

    /**
     * 注册公司
     * @param $_data
     * @param string $permanetCode
     */
    public static function registerCompany($_data,$permanetCode='')
    {
        $DTCompanyModel = new CompanyRegister();
        $data = [];
        $data['company_name'] = $_data['corp_name'] ?? '';
        $data['corpid'] = $_data['corpid'] ?? '';
        $data['industry'] = $_data['industry'] ?? '';
        $data['corp_logo_url'] = $_data['corp_logo_url'] ?? '';
        $data['register_time'] = date('Y-m-d H:i:s',time());
        $data['permanent_code'] = $permanetCode;
        try {
            $DTCompanyModel->save($data);
        } catch (\Exception $e) {
            throw new MyException(10001, $e->getMessage());
        }
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
     public function getSuiteAccessToken($suiteTicket)
    {
        $suiteAccessToken = $this->ISVService->getSuiteAccessToken($suiteTicket);
        return $suiteAccessToken;
    }

    //isv应用免登陆的公司AccessToken
    public function getIsvCorpAccessToken($corpId)
    {
        //授权方企业ID
        $authCorpId = CORP_ID;
        $suiteKey = 'suiteTicket_' . $authCorpId;
        //获取票据信息
        $ticketData = self::getAuthOrTicketInfo($authCorpId, 2);
        //获取授权信息
        $authData = self::getAuthOrTicketInfo($corpId, 4);

        $ticketDatArr = \GuzzleHttp\json_decode($ticketData['biz_data'], true);
        $authDataArr = \GuzzleHttp\json_decode($authData['biz_data'], true);
        if (Cache::get($suiteKey)) {
            $suiteAccessToken = Cache::get($suiteKey);
        } else {
            $suiteAccessToken = $this->getSuiteAccessToken($ticketDatArr['suiteTicket']);
        }

        $accessTokenkey = "IsvCorpAccessToken_" . $corpId;
        if (Cache::get($accessTokenkey)) {
            $isvCorpAccessToken = Cache::get($accessTokenkey);
        } else {
            $isvCorpAccessToken = $this->ISVService->getIsvCorpAccessToken($suiteAccessToken, $corpId, $authDataArr['permanent_code']);
        }

        $key = 'corpAuthInfo_'.$corpId;
        if (!Cache::get($key)) {
            Cache::set($key, json_encode($authDataArr['auth_info']), 86400); //缓存1天时间
        }

        return $isvCorpAccessToken;

    }

    //获取订单公司授权信息 cache数据
    public function getIsvCorpAuthInfo($corpId)
    {
        $key = 'corpAuthInfo_'.$corpId;
        //return json_decode($this->Auth->cache->getAuthInfo($key),true);
        return json_decode(Cache::get($key),true);
    }


    /**
     * @Route("DTGetUserInfo", method="POST")
     */

    //获取钉钉员工详细信息
    public function DTGetUserInfo()
    {
       $userid = input('userid','');
       $corpId = input('corpId','');

       if(!$userid || !$corpId){
         return  json_error(20005);
       }

       //查询后台是否完成了公司的设置
       $completeSysConfKey = "completeSysConf:" . $corpId;
       //先读缓存 若缓存没有再读数据库  数据库查询不到抛出未完成公司设置异常
       if(!Cache::get($completeSysConfKey) && !DingcanSysconfig::isCompleteSysConf($corpId)){
         return  json_error(20950);
       };

       $DTUserModel = new DTUser;
       $DTDepartmentModel = new DTDepartment;
       
       $isReg = $DTUserModel->where('platform_staffid =:platform_staffid and cropid=:cropid', ['platform_staffid' => $userid, 'cropid' => $corpId])->find();

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
           
           return json_ok($userInfo);

       }
          
       //员工身份 统一userid字段
       $isReg->userid = $isReg->staffid;

       //判断redis缓存是否有access_key 并且 未过期
       $isReg['access_key'] = AccessKeyHelper::getAccessKey($isReg->userid);
       if(!$isReg['access_key']){
          //生成的access_key
          $isReg['access_key'] = AccessKeyHelper::generateAccessKey($isReg->userid); 
       }

       //老用户查询后返回数据库结果
       return json_ok($isReg);

    }


    /**
     * @Route("backendDTGetUserInfo", method="POST")
     */

    //获取钉钉员工详细信息(后台)
    public function backendDTGetUserInfo()
    {
       $userid = input('userid','');
       $corpId = input('corpId','');

       if(!$userid || !$corpId){
         return  json_error(20005);
       }

       $DTUserModel = new DTUser;
       $DTDepartmentModel = new DTDepartment;
    
       $isReg = $DTUserModel->where('platform_staffid =:platform_staffid and cropid=:cropid', ['platform_staffid' => $userid, 'cropid' => $corpId])->find();

       if(!$isReg){
           //新用户（管理员） 注册逻辑
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
         
           return json_ok($userInfo);

       }

       //维护管理员登录时间login_time login_ip字段 同时把返回前端信息换成管理员数据库信息
       CompanyAdmin::updateAdminInfo($corpId,$userid);
       //员工身份 统一userid字段
       $isReg->userid = $isReg->staffid;

       //判断redis缓存是否有access_key 并且 未过期
       $isReg['access_key'] = AccessKeyHelper::getAccessKey($isReg->userid);
       if(!$isReg['access_key']){
          //生成的access_key
          $isReg['access_key'] = AccessKeyHelper::generateAccessKey($isReg->userid); 
       }

       //老用户查询后返回数据库结果
       return json_ok($isReg);

    }


     /**
     * @Route("DTGetDepartment")
     */

    //获取钉钉企业部门信息
    public function DTGetDepartment($corpId='')
    {
       //$corpId = input('corpId','');

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

        //获取订餐截止时间 start
        $sys_conf = DingcanSysconfig::getSysConfByCorpId($corpId);
        if(!$sys_conf){
            return  json_error(20090);
        }
  
        $dingcan_conf = checkDingcanStauts($sys_conf);
        $baomingEndTime = date('H:i',$dingcan_conf['baomingEndTimeStamp']);
        $content = "可以开始订晚餐了,{$baomingEndTime}截止哦。点下面的按钮开始点餐。";
        //获取订餐截止时间 end

        //动作卡方式
        $opt['msg']['msgtype'] = 'action_card';
        $sub_data['title'] = "天天点餐";
        $sub_data['markdown'] = $content;
        $sub_data['single_title'] = "立即订餐";
        $sub_data['single_url'] = "eapp://pages/orderIndex/orderIndex";
        $opt['msg']['action_card'] = $sub_data;

        //文本方式
        // $opt['msg']['msgtype'] = 'text';
        // $opt['msg']['text'] = ['content'=>$content];

        $departmentid_list_arr = DTDepartment::getDingDepartmentIds($corpId);
        if(!$departmentid_list_arr){
            return  json_error(20900);
        } 
        //ISV场景：钉钉接口不能全员发送工作消息，分割数组为两部分
        $flag = 1;//默认工作消息全部发送成功
        if(count($departmentid_list_arr) == 1){

            //只有一个部门 按照进入订餐应用实际注册的钉钉Userid来发送工作消息
            $userid_list_arr = CompanyStaff::getDingdingUserIds($corpId);
            if(!$userid_list_arr){
                return  json_error(20900);
            }

            $userid_list = implode(',', $userid_list_arr);

            $opt['userid_list'] = $userid_list;
             
            $res = $Message->corpConversation($isvCorpAccessToken,$opt);

        }else{
            
            //若多个部门  每5个部门为一组发送工作消息   避免全员发送消息不成功情况
            $departmentid_five_arr = array_chunk($departmentid_list_arr, 5);

            foreach ($departmentid_five_arr as $k1 => $v1) {
              
              $departmentid_list = implode(',', $v1);

              $opt['dept_id_list'] = $departmentid_list;
   
              $res = $Message->corpConversation($isvCorpAccessToken,$opt);

              if($res->errcode == 0){
                  $msg = "发送订餐消息成功：对应公司corpId:{$corpId},agentid:{$agentid} ,钉钉接口返回： ". json_encode($res,JSON_UNESCAPED_UNICODE) . ' 对应发送部门的id： ' . $opt['dept_id_list'];
                  Log::info($msg);
                 
              }else{
                  $flag = 0;
                  $msg = "发送订餐消息失败：对应公司corpId:{$corpId},agentid:{$agentid} ,钉钉接口返回： ". json_encode($res,JSON_UNESCAPED_UNICODE)  . ' 对应发送部门的id： ' . $opt['dept_id_list'];
                  Log::error($msg);
                  Log::error('opt:' . json_encode($opt,JSON_UNESCAPED_UNICODE));
                
              }

            }

        }

        if($flag == 1){
            //$msg = "发送订餐消息成功：对应公司corpId:{$corpId},agentid:{$agentid} ,钉钉接口返回： ". json_encode($res,JSON_UNESCAPED_UNICODE);
            //Log       ::info($msg);
            return $corpId;
        }else{

            //$msg = "发送订餐消息失败：对应公司corpId:{$corpId},agentid:{$agentid} ,钉钉接口返回： ". json_encode($res,JSON_UNESCAPED_UNICODE);
            //Log::error($msg);
            //Log::error('opt:' . json_encode($opt,JSON_UNESCAPED_UNICODE));
            return false;
        }
    }


    /**
     * @Route("departmentStatus")
     */
    //钉钉部门变更事件 主动轮训该方法
    //备注：授权微应用的企业，发生部门的增加、删除、修改的时刻推送，插入表open_sync_biz_data_medium
    public function departmentStatus()
    {
      $infos = Db::connect('yun_push')
            ->table('open_sync_biz_data_medium')
            ->order('id desc')
            ->where(['biz_type' => 14,'status'=> 0])
            ->select();

      if(!$infos){
          return json_ok(); 
      }

      foreach ($infos as $k => $v) {
        # code...
        $data = json_decode($v['biz_data'], true);
        echo '<pre>';var_dump($data);
      }
    }


// {"errcode":0,"userPermits":"","userPerimits":"","syncAction":"org_dept_create","outerDept":false,"errmsg":"ok","deptManagerUseridList":"","parentid":1,"groupContainSubDept":false,"outerPermitUsers":"","outerPermitDepts":"","deptPerimits":"","createDeptGroup":true,"name":"技术部","id":364066223,"autoAddUser":true,"deptHiding":false,"deptPermits":"","order":364066223,"syncSeq":"049D21CC8562A0F22301A9E776"}


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
