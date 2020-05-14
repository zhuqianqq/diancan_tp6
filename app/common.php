<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key  = md5(empty($key) ? config('system.pass_salt') : $key);
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }

    $str = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
    return strtoupper(md5($str)) . $str;
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 */
function think_decrypt($data, $key = '')
{
    $key  = md5(empty($key) ? config('system.pass_salt') : $key);
    $data = substr($data, 32);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data   = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data   = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 请求正确返回
 * @param string $msg
 * @param array $data
 * @return json
 */
function json_ok($data = [], $code = 10000, $status = 1, $msg = '')
{
    $result['status'] = $status;
    $result['data']   = $data;
    $result['msg']    = isset(config('error')[$code]) ? config('error')[$code] : '';
    $result['code']   = $code;
    return json($result);
}

/**
 * 请求错误返回
 * @param string $code
 * @param string $msg
 * @return json
 */
function json_error($code = 10001, $msg = '')
{
    if ($msg == '') {
        $result['msg'] = isset(config('error')[$code]) ? config('error')[$code] : '';
    } else {
        $result['msg'] = $msg;
    }
    $result['status'] = 0;
    $result['code']   = $code;
    return json($result);
}

/**
 * 用户密码加密方法，可以考虑盐值包含时间（例如注册时间），
 * @param string $pass 原始密码
 * @return string 多重加密后的32位小写MD5码
 */
function encrypt_pass($pass)
{
    if ('' == $pass) {
        return '';
    }
    $salt = config('app.pass_salt');
    return md5(sha1($pass) . $salt);
}

/**
 * 数据 类型转换
 * @access protected
 * @param  mixed $value 值
 * @param  string|array $type 要转换的类型
 * @return mixed
 */
function transform($value, $type)
{
    if (is_null($value)) {
        return;
    }

    if (is_array($type)) {
        [$type, $param] = $type;
    } elseif (strpos($type, ':')) {
        [$type, $param] = explode(':', $type, 2);
    }

    switch ($type) {
        case 'string':
            $value = (string)$value;
            break;
        case 'integer':
            $value = (int)$value;
            break;
        case 'float':
            if (empty($param)) {
                $value = (float)$value;
            } else {
                $value = (float)number_format($value, (int)$param, '.', '');
            }
            break;
        case 'boolean':
            $value = (bool)$value;
            break;
        case 'timestamp':
            if (!is_numeric($value)) {
                $value = strtotime($value);
            }
            break;
        case 'datetime':
            $value = is_numeric($value) ? $value : strtotime($value);
            if (empty($param)) {
                $value = date('Y-m-d H:i:s', $value);
            } else {
                $value = date($param, $value);
            }
            break;
        case 'object':
            if (is_object($value)) {
                $value = json_encode($value, JSON_FORCE_OBJECT);
            }
            break;
        case 'array':
            $value = (array)$value;
        case 'json':
            $option = !empty($param) ? (int)$param : JSON_UNESCAPED_UNICODE;
            $value  = json_encode($value, $option);
            break;
        case 'serialize':
            $value = serialize($value);
            break;
        default:
            break;
    }

    return $value;
}

/**
 * 判定今天是否为工作日的外网接口 文档 https://www.kancloud.cn/xiaoggvip/holiday_free
 * 请求地址 http://tool.bitefu.net/jiari/  请求方式 POST , GET
 * @param  d 日期
 * @return int 0工作日 1 假日 2节日
 */
function isWorkDay()
{
    $api_url = 'http://tool.bitefu.net/jiari/';
    $today = date('Ymd');
    $Http = new \app\util\Http();
    $res = $Http->get($api_url,['d'=>$today]);

    if ($res == 0) {
        return ['res'=> 1,'msg'=>'工作日','nextWorkDay'=>''];
    } else {
        $i = 1;
        do {
            $checkDay = date('Ymd',strtotime("+$i day"));
            //$checkDay = date("Ymd",strtotime("+$i day",strtotime("20200501"))); //测试用  指定日期增加天数
            if($Http->get($api_url,['d'=>$checkDay]) == 0){
                break;
            }
            $i++;
        } while ( $i <= 8);

        return ['res'=> 0,'msg'=>'非工作日','nextWorkDay'=>$checkDay];
    }
}

//判断工作日 直接读取本地已经生成的工作日的js文件
function isWorkDayJs()
{
    try {
        $filename = app()->getRootPath() . 'public/js/2020_workday.js';
        $_workdayArr = json_decode(file_get_contents($filename),true);
        $workdayArr = $_workdayArr['2020'];
    } catch (Exception $e) {
        throw new \app\MyException(20700);
    }
    $today = date('md');
    //echo '<pre>';var_dump($workdayArr);
     //判断星期几; 数字度0表示是星期天,数字123456表示星期一到六知
    $no = date("w");
    //不为节假日  不是周六与周日默认为工作日
    if(!isset($workdayArr[$today]) && $no != 0 && $no != 6){
        return ['res'=> 1,'msg'=>'工作日','nextWorkDay'=>''];
    }else{
        $i = 1;
        do {
            $checkDay = date('md',strtotime("+$i day"));
            //$checkDay = date("md",strtotime("+$i day",strtotime("20200501"))); //测试用  指定日期增加天数
            // echo $i .'<br>'; echo $checkDay . '<br>';
            if(!isset($workdayArr[$checkDay])){
                break;
            }
            $i++;
        } while ( $i <= 8);

        return ['res'=> 0,'msg'=>'非工作日','nextWorkDay'=>$checkDay];
    }
}

/**
 * 获取
 * @return array|false|string
 */
function GetIp()
{
    $realip  = '';
    $unknown = 'unknown';
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($arr as $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } else if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], $unknown)) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $realip = $_SERVER['REMOTE_ADDR'];
        } else {
            $realip = $unknown;
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)) {
            $realip = getenv("REMOTE_ADDR");
        } else {
            $realip = $unknown;
        }
    }
    $realip = preg_match("/[\d\.]{7,15}/", $realip, $matches) ? $matches[0] : $unknown;
    return $realip;
}


/**
 * 系统订餐截止时间
 * @param  `end_time_type` '订餐截止时间 0-送餐前30分钟  1-送餐前1小时  2-送餐前2小时',
 * @return int 分钟
 */
function confEndTimeType($end_time_type = 1)
{

    switch ($end_time_type){
        case 0:$minutes = 30;break;
        case 1:$minutes =  60;break;
        case 2:$minutes =  120;break;
    }
    return $minutes;
}


/**
 * 自动消息提醒时间
 * @param  `end_time_type` '订餐截止时间 1-送餐前1小时  2-送餐前2小时  3-送餐前3小时',
 * @return int 分钟
 */
function sendMessageTimeType($message_time_type = 1)
{
    switch ($message_time_type){
        case 1:$seconds = 60*60;break;
        case 2:$seconds = 60*60*2;break;
        case 3:$seconds = 60*60*3;break;
    }
    return $seconds;
}

/**
 * 分析订餐状态
 * @param  `end_time_type` '订餐截止时间 0-送餐前30分钟  1-送餐前1小时  2-送餐前2小时',
 * @return DingcanStauts： 0 订餐未开始(默认)  1 订餐报名中 2 报名结束送餐中 3 送餐完毕
 *         DingcanDay： 0 非订餐日  1 订餐日
 *         baomingEndTimeStamp：报名截止时间戳
 *         send_time_key 1 上午  2 下午
 *         nextWorkDay 工作日是为''  假期是为下个工作日
 */
function checkDingcanStauts($sysConf)
{
    //1.判断配置的订餐日信息
    $isMutiChoose = strpos($sysConf['dc_date'], ',');
    $DingcanDay = 0; //默认不是订餐日
    $nextWorkDay = ''; //默认下个工作日为空
    //现在的时间戳
    $nowTimeStamp = strtotime("now");
    //判断星期几; 数字度0表示是星期天,数字123456表示星期一到六知
    $no = date("w");
    if ($no == 0) {
        $no = 7;
    }

    if (!$isMutiChoose !== false) {
        //判断工作日
        if ($sysConf['dc_date'] == 0) {
             $isWorkDay = isWorkDayJs();
             if ($isWorkDay['res'] == 1) {
                $DingcanDay = 1;
             } else {
                $nextWorkDay = $isWorkDay['nextWorkDay'];
             }
        //判断具体日期         
        } else {
            if ($sysConf['dc_date'] == $no) {
                $DingcanDay = 1;
            }
        }
    } else {
         if (strpos($sysConf['dc_date'], $no) !== false) {
                $DingcanDay = 1;
         } else {
            //是否都选工作日
            if(strpos($sysConf['dc_date'], '0') !== false) {
                 $isWorkDay = isWorkDayJs();
                 if ($isWorkDay['res'] == 1) {
                    $DingcanDay = 1;
                 } else {
                    $nextWorkDay = $isWorkDay['nextWorkDay'];
                 }
            }
         }
    }
    
    $DingcanStauts = 0; //0 订餐未开始(默认)  1 订餐报名中 2 报名结束送餐中 3 送餐完毕
    $baomingEndTimeStamp = 0; //订餐报名截止时间  默认0
    $send_time_key = 0;
    $send_time_text = '';

    //送餐日
    if ($DingcanDay == 1) {
        //判断上午还是下午
        $no = date("H",time());
        if ($no < 14){
            $send_time_key = 1;
        } else {
            $send_time_key = 2;
        }
        //获取具体的送餐时间
        $send_time_info = json_decode($sysConf['send_time_info'],true);
        //判断是否报餐中餐与晚餐[1,2] 或只报中餐 1 或只报晚餐 2;
        $send_time_info_keys = array_keys($send_time_info);
        //只报中餐 1 或只报晚餐 2 ：则不按照上午还是下午的时间判断 
        if (!in_array($send_time_key, $send_time_info_keys)) {
            $send_time_key = $send_time_info_keys[0];
        }

        if ($send_time_key == 1) {
            $send_time_text = '上午';
        } else {
            $send_time_text = '下午';
        }

        $send_time = $send_time_info[$send_time_key];
        $send_time_str = date('Y-m-d',time()).$send_time.':00';
        //获取报餐提前多久的系统设置
        $confEndTime = confEndTimeType($sysConf['end_time_type'])*60;
        //送餐时间戳
        $sendTimeStamp = strtotime($send_time_str);
        //报名截止时间戳
        $baomingEndTimeStamp = $sendTimeStamp - $confEndTime;
        
        if($nowTimeStamp < $baomingEndTimeStamp){
            $DingcanStauts = 1;//订餐日 报名中
        }else if(($nowTimeStamp >= $baomingEndTimeStamp) && ($nowTimeStamp < $sendTimeStamp)){
            $DingcanStauts = 2;//订餐日 报名已截止 送餐中
        } else {
            $DingcanStauts = 3;//订餐日 送餐完毕
        }
    }

    return [
        'isDingcanDay' => $DingcanDay,
        'DingcanStauts' => $DingcanStauts,
        'baomingEndTimeStamp'=>$baomingEndTimeStamp,
        'send_time_key' => $send_time_key,
        'send_time_text' => $send_time_text,
        'nextWorkDay' => $nextWorkDay,
        'nowTimeStamp' => $nowTimeStamp
    ];
    
}

/**
 * 验证金额
 * @param $value
 * @return bool
 */
function checkMoney($value)
{
    $flag = true;
    $money_reg = '/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/';
    if (!preg_match($money_reg, $value)) {
        $flag = false;
    }
    return $flag;
}


//H5页面的token值
function setH5token($eatery_id,$eat_type)
{
    $param = ['eatery_id'=>$eatery_id,'eat_type'=>$eat_type];
    return base64_encode(json_encode($param));
}

//获取H5页面的token值
function getH5token($token)
{
    return json_decode(base64_decode($token),true);
}

/**
 * 获取统计时间
 * @param $type
 * 1 本周
 * 2 上周
 * 3 本月
 * 4 近30天
 * @return array
 */
function getDateInfo($type)
{
    if (is_null($type)) {
        return;
    }

    switch ($type) {
        case 'recent7days':
            $res = array(
                'start_time' => date('Y-m-d 00:00:00', strtotime("-6 day")),
                'end_time' => date('Y-m-d H:i:s'),
            );
            break;
        case 'thisWeek':
            $res = array(
                'start_time' => date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"))),
                'end_time' => date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"))),
            );
            break;
        case 'preWeek':
            $res = array(
                'start_time' => date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y"))),
                'end_time' => date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y"))),
            );
            break;
        case 'thisMonth':
            $res = array(
                'start_time' => date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y"))),
                'end_time' => date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y"))),
            );
            break;
        case 'recent30days':
            $res = array(
                'start_time' => date('Y-m-d 00:00:00', strtotime("-29 day")),
                'end_time' => date('Y-m-d H:i:s'),
            );
            break;
        default:
            break;
    }

    return  $res;
}


