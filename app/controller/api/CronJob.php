<?php
declare (strict_types=1);

namespace app\controller\api;

use app\controller\api\Base;
use think\annotation\route\Group;
use think\annotation\Route;
use app\model\CompanyRegister;
use app\controller\api\Dingtalk;
use app\model\DingcanSysconfig as DS;
/**
 * 非用户身份类接口
 * Class Index
 * @package app\controller\api
 * @author  2066362155@qq.com
 * @Group("api/CronJob")
 */
class CronJob extends Base
{
    /**
     * @Route("sendMessage", method="GET")
     */
    public function sendMessage()
    {
        $companys = CompanyRegister::column('company_id,corpid');
        $company_ids = $corpids = [];
        foreach ($companys as $k1 => $v1) {
        	$company_ids[] = $v1['company_id'];
        	$corpids[] = $v1['corpid'];
        }
        
        $sysConfs = DS::where('company_id','in',$company_ids)->select();


        dd($sysConfs);


        dd($companys);
        $dingTalk = new Dingtalk();
        // foreach ($variable as $key => $value) {
        // 	$dingTalk->sendMessage();
        // }
       
    }

    public function checkNewsTime($sysConfs){
    	if(!$sysConfs){
    		return false;
    	}
    	$news_time = $sysConfs->news_time;
    	

    }
}
