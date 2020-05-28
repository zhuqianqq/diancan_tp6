<?php
/**
 * 跨域中间件
 */
namespace app\middleware;

use think\middleware\AllowCrossDomain;

class AllowCors extends AllowCrossDomain
{
    protected $header = [
//        'Access-Control-Allow-Origin'     => '*',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With,access-key,user-id',
    ];
}
