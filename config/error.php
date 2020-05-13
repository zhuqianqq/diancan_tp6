<?php
// +----------------------------------------------------------------------
// | 错误码定义
// +----------------------------------------------------------------------

return [

    //错误码定义
    /************
     * 10001-10999  系统相关错误；
     * 11001-11999  接口验证相关；
     * 1201-12999， 具体业务相关；
     * 13001-13999  第三方相关；
     * 14001-14999  保留；
     * 15001-15999  保留；
     * .........
     *
     * 19001-19999 预留外部应用使用；
     ***********/

    //10001-10999  系统相关错误
    10000 => '无错误',
    10001 => '系统错误',
    10002 => '参数缺少',
    10003 => '配置缺少',
    10004 => '参数错误',
    10005 => '保存失败！',
    10006 => '保存成功！',
    10007 => '删除失败！',
    10008 => '删除成功！',
    10009 => '不能选择自己作为上级！',
    10010 => '请上传图片！',
    10011 => '请上传视频！',
    10012 => '上传失败！',
    10013 => '上传失败！',

    //11001-11999，接口验证相关
    11001 => 'appid不存在',
    11002 => 'appId已被禁用',
    11003 => '签名参数缺失',
    11004 => '接口签名验证失败',

    11101 => '用户访问令牌缺失',
    11102 => '用户访问令牌过期',
    11103 => '用户在其它设备登录',
    11104 => '用户不存在',
    11105 => '用户密码错误',
    11106 => '用户已被禁用',
    11107 => '用户组已被禁用',
    11108 => '登录成功',

    //* 12001-12999， 用户业务相关；
    12001 => '用户已被绑定！',
    12002 => '用户绑定失败！',
    12003 => '获取用户session_key失败',
    12004 => '用户session_key无效',
    12005 => '用户数据签名验证失败',
    12006 => '微信数据解析失败',
    12007 => '请输入账号',
    12008 => '请输入密码',

    //*餐馆业务相关
    13001 => '参数缺少',
    13002 => '数据丢失',
    13003 => '操作成功',
    13004 => '操作失败',
    13005 => '该餐馆有订单记录，不可删除',
    13100 => '该电话号码已被注册',

    //*菜品业务相关
    14001 => '参数缺少',
    14002 => '数据丢失',
    14003 => '操作成功',
    14004 => '操作失败',
    14005 => '参数格式错误',

    //*系统设置业务相关
    15001 => '参数缺少',
    15002 => '数据丢失',
    15003 => '操作成功',
    15004 => '操作失败',

    //*员工订餐业务相关
    16001 => '参数缺少',
    16002 => '数据丢失',
    16003 => '参数格式错误',
    16004 => '当前状态不可订餐',

    //* 20001-20999， 钉钉接口业务相关；
    20001 => '公司ID或code码为空！',
    20002 => '公司ID为空！',
    20005 => '公司ID或userid为空！',
    20010 => '获取用户信息失败！',
    20020 => '新用户注册失败！',
    20050 => '公司未注册！',
    20060 => '公司部门信息注册失败！',
    20700 => '无工作日相关js',
    20800 => '公司授权信息agentid为空！',
    20900 => '对应公司userid_list为空',
    20901 => '无需要发送工作消息的公司',

   


];
