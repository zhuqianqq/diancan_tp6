<?php
declare (strict_types=1);

namespace app\middleware;

use app\util\AccessKeyHelper;
use app\model\CompanyAdmin;
use app\model\CompanyStaff;

class AccessCheck
{
    /**
     * 处理请求
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {

        //return $next($request);
        $request_uri = $request->request()['s'] ?? '';
        if(!$request_uri){
            throw new \app\MyException(11101);
        }
        //钉钉相关接口不需要判断access-key ， H5餐馆订单详情页面不需验证
        if(stripos($request_uri,'dingtalk') !== false  || stripos($request_uri,'eateryOrderDetail') !== false){
            return $next($request);
        }
        
        $user_id = intval($request->header('user-id') ?? 0);
        $access_key = $request->header('access-key','');

        if($user_id <= 0 || empty($access_key)){
            throw new \app\MyException(11101);
            //$res = ['user_id'=>$user_id,'access_key'=>$access_key];
            //throw new \app\MyException(11101,json_encode($res));
        }

        $check = AccessKeyHelper::validateAccessKey($user_id,$access_key);
        if(!$check){
            throw new \app\MyException(11102);
        }

        $request_uid = $request->param('user_id') ?? $request->param('staffid');
        //判断是否为管理员身份
        $userInfo = CompanyStaff::where('staffid = :user_id',['user_id' => $user_id])->field('platform_staffid')->find();
        $isAdmin = CompanyAdmin::where('platform_userid = :user_id',['user_id' => $userInfo['platform_staffid']])->find();
        if(!$isAdmin){

            if($user_id != $request_uid){
                throw new \app\MyException(10015);
            }
        }else{
            // echo $request_uid . '===========';
            // echo $isAdmin['userid'] . '===========';
            // echo $user_id . '===========';
            // die;
            if(($request_uid != $isAdmin['userid']) && ($request_uid != $user_id  )){
                throw new \app\MyException(10015);
            }
        }
        
        //$request->user_id = $user_id;
        $request->access_key = $access_key;

        return $next($request);
    }
}
