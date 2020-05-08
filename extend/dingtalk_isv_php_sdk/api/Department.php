<?php
require_once(__DIR__ . "/../util/Http.php");

class Department
{
    private $http;
    public function __construct() {
        $this->http = new Http();
    }

    public function createDept($accessToken, $dept)
    {
        $response = $this->http->post("/department/create", 
            array("access_token" => $accessToken), 
            json_encode($dept));
        return $response;
    }
    
    
    public function listDept($accessToken)
    {
        $response = $this->http->get("/department/list", 
            array("access_token" => $accessToken));
        return $response;
    }

     public function detailDept($accessToken, $id)
    {
        $response = $this->http->get("/department/get", 
            array("access_token" => $accessToken,"id" => $id));
        return $response;
    }
    
    
    public function deleteDept($accessToken, $id)
    {
        $response = $this->http->get("/department/delete", 
            array("access_token" => $accessToken, "id" => $id));
        return $response;
    }
}