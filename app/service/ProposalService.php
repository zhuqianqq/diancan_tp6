<?php
declare (strict_types=1);
namespace app\service;

use app\model\DingcanSysconfig as DF;
use app\model\Proposal;
use app\MyException;
use app\traits\ServiceTrait;
use app\model\CompanyAdmin;

/**
 * 意见反馈
 * Class ProposalService
 * @package app\service
 * @author  2066362155@qq.com
 */
class ProposalService
{
    use ServiceTrait;

    /**
     * 订餐设置
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function feedBack()
    {
        //获取用户信息
        $user_id = input('user_id', '', 'int');
        $mobile = input('mobile', '', 'string');
        $content = input('content', '', 'string');
        if (!$user_id || !$mobile || !$content) {
            return json_error(15001);
        }

        $userInfo = CompanyAdmin::where('userid = :user_id', ['user_id' => $user_id])->find();
        if (!$userInfo) {
            throw new MyException(15002);
        }
        $data['company_id'] = $userInfo['company_id'];

        try {
            $proM = new Proposal;
            $proM->company_id = $userInfo->company_id;
            $proM->staff_name = $userInfo->real_name;
            $proM->mobile = $mobile;
            $proM->content = $content;
            $proM->save();
        } catch (\Exception $e){
            throw new MyException(10001, $e->getMessage());
        }
        return [];
    }


    public static function feedBackList()
    {

      $list = Proposal::alias('p')
            ->join('company_register c','p.company_id = c.company_id')
            ->field('p.*,c.company_name')
            ->order('create_time','desc')
            ->select();

      return $list;

    }
    

}
