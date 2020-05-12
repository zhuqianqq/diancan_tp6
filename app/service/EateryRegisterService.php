<?php
declare (strict_types=1);
namespace app\service;

use app\model\Eatery;
use app\traits\ServiceTrait;
use app\model\EateryRegister as ER;
use app\MyException;
use think\facade\Db;
use app\model\CompanyAdmin;
use app\model\Order;

/**
 * 餐馆
 * Class EateryRegisterService
 * @package app\service
 * @author  2066362155@qq.com
 */
class EateryRegisterService
{

    //仓库，带命名空间
    public static $repository = 'app\repository\EateryRegisterRepository';

    use ServiceTrait;

    /**
     * 更新或者创建餐馆
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function registerEatery($data)
    {
        $eatery_id =  $data['eatery_id'] ?? 0;
        $userId = $data['user_id'];
        $userInfo = CompanyAdmin::where('userid=:userid', ['userid' => $userId])->find();
        if (!$userInfo) {
            throw new MyException(13002);
        }
        $company_id = $userInfo->company_id;

        Db::startTrans();
        if ($eatery_id==0) {//新增
            try {
                $data['password'] = md5('12345');
                $data['eat_type'] = str_replace('，',',', $data['eat_type']);
                $oneEateryR = new ER;
                $oneEateryR->save($data);

                $oneEatery = new Eatery();
                $oneEatery->company_id = $company_id;
                $oneEatery->eatery_id = $oneEateryR->eatery_id;
                $oneEatery->eatery_alias_name = $oneEateryR->eatery_name;
                $oneEatery->eat_type = str_replace('，',',', $oneEateryR->eat_type);
                $oneEatery->save();
                Db::commit();
                return [];
            } catch (\Exception $e){
                Db::rollback();
                throw new MyException(13100);
            }
        } else { //编辑
            try {
                if (!$eatery_id) {
                    throw new MyException(13001);
                }
                $oneEateryR = self::$repository::getInfoById($data['eatery_id']);
                if (!$oneEateryR) {
                    throw new MyException(13002);
                }
                $oneEateryR->save($data);

                $oneEatery = Eatery::where('eatery_id', $oneEateryR->eatery_id)->find();
                $oneEatery->eatery_alias_name = $oneEateryR->eatery_name;
                $oneEatery->eat_type = str_replace('，',',', $oneEateryR->eat_type);
                $oneEatery->save($data);
                Db::commit();
                return [];
            }catch (\Exception $e){
                throw new MyException(13001, $e->getMessage());
                Db::rollback();
            }
        }
    }

    /**
     * 删除餐馆
     * @param array $data
     * @return array 对象数组
     * @throws \app\MyException
     */
    public static function eateryDelete(){
        $eatery_id = input('eatery_id');
        $user_id = input('user_id');
        
        $oneEateryRegister = self::$repository::getInfoById($eatery_id);
        $oneEatery = Eatery::where('eatery_id=:eatery_id', ['eatery_id' => $eatery_id])->find();
        if (!$oneEateryRegister || !$oneEatery) {
            throw new MyException(13002);
        }
        $compAndDeptInfo = getCompAndDeptInfoById($user_id);
        //获取订餐记录
        $where = ['company_id'=>$compAndDeptInfo['company_id'], 'eatery_id'=>$eatery_id];
        $eateryRecord = Order::where('company_id=:company_id and eatery_id=:eatery_id', $where)->select();

        if ($eateryRecord->count()==0) {
            Db::startTrans();
            //物理删除
            try {
                $oneEateryRegister->delete();
                $oneEatery->delete();
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                throw new MyException(13001, $e->getMessage());
            }
        } else {
            //软删除
            try{
                $oneEatery->is_delete = 1;
                $oneEatery->save();
            }catch (\Exception $e){
                throw new MyException(13001, $e->getMessage());
            }
        }

        return [];
    }

}
