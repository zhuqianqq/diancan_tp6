<?php
require_once(__DIR__ . "/../util/Http.php");

class Role
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }   

    //获取角色列表
    public function getRoleList($accessToken)
    {
        $response = $this->http->get("/topapi/role/list",
            array("access_token" => $accessToken));
        return $response;
    }


    //获取角色组信息
    public function getRoleGroup($accessToken,$group_id)
    {
        $response = $this->http->get("/topapi/role/getrolegroup",
            array("access_token" => $accessToken,'group_id' => $group_id));
        return $response;
    }

    //获取角色详情
    public function getRoleInfo($accessToken,$roleId)
    {
        $response = $this->http->get("/topapi/role/getrole",
            array("access_token" => $accessToken,'roleId' => $roleId));
        return $response;
    }

}