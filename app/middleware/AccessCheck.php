<?php
declare (strict_types=1);

namespace app\middleware;

use app\util\AccessKeyHelper;

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
       
        $request_uri = $request->request()['s'] ?? '';
        if(!$request_uri){
            throw new \app\MyException(11101);
        }
        //钉钉相关接口不需要判断access-key ， 获取access-key接口不需验证
        if(stripos($request_uri,'dingtalk') !== false  || stripos($request_uri,'adminInfo') !== false){
            return $next($request);
        }

        $user_id = intval($request->header('user-id') ?? $request->param('user_id'));
        $access_key = $request->header('access-key','');

        if($user_id <= 0 || empty($access_key)){
            throw new \app\MyException(11101);
        }

        $check = AccessKeyHelper::validateAccessKey($user_id,$access_key);
        if(!$check){
            throw new \app\MyException(11102);
        }

        $request->user_id = $user_id;
        $request->access_key = $access_key;

        return $next($request);
    }
}
