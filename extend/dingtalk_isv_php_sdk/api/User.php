<?php
require_once(__DIR__ . "/../util/Http.php");

class User
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }   

    public function getUserInfo($accessToken, $code)
    {
        $key = "UserInfo_".$accessToken;
        $UserInfo = \think\facade\Cache::get($key);
        if (!$UserInfo)
        {
            $UserInfo = $this->http->get("/user/getuserinfo",
                array("access_token" => $accessToken, "code" => $code));
            \think\facade\Cache::set($key, $UserInfo, 60*60*2);//缓存两天
        }

        return $UserInfo;
    }

    public function get($accessToken, $userId)
    {
        $response = $this->http->get("/user/get",
            array("access_token" => $accessToken, "userid" => $userId));
        return $response;
    }

    public function simplelist($accessToken,$deptId){
        $response = $this->http->get("/user/simplelist",
            array("access_token" => $accessToken,"department_id"=>$deptId));
        return $response;

    }
}