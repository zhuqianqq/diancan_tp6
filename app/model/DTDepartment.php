<?php
declare (strict_types=1);
namespace app\model;

use app\traits\ModelTrait;
use think\facade\Db;
use app\model\CompanyRegister;

/**
 * 用户
 * Class User
 * @package app\model
 * @author  2066362155@qq.com
 */
class DTDepartment extends BaseModel
{

    use ModelTrait;

    protected $table = "dc_company_department";
    public  static $_table  = "dc_company_department";


    //注册
    public function registerDepartment($_data, $company_id)
    {
        $data = [];

        foreach ($_data->department as $k => $v) {
            # code...
            $data[$k]['company_id'] = $company_id;
            $data[$k]['platform_departid'] = $v->id ?? '';
            $data[$k]['dept_name'] = $v->name ?? '';
            $data[$k]['parentid'] = $v->parentid ?? 0;
            $data[$k]['sort'] = $v->detail->order ?? $v->id;
            $data[$k]['dept_hiding'] = $v->detail->deptHiding ? 1 : 0;
            $data[$k]['sourceIdentifier'] = $v->sourceIdentifier ?? '';
            $data[$k]['create_time'] = date('Y-m-d H:i:s',time());
        }
        //批量更新
        self::saveAll($data);

        //将dc_company_department 的 parent_id关联在id上
        $companys = self::where('company_id',$company_id)->select();

        foreach ($companys as $k2 => $v2) {
            if($v2->parentid == 0){
                continue;
            }
            $_parentid = self::where('platform_departid',$v2->parentid)->where('company_id',$company_id)->value('id');
            self::where('id',$v2->id)->update(['parentid'=>$_parentid]);
        }

    }

     //获取钉钉对应公司的部门id
    public static function getDingDepartmentIds($corpId)
    {
        $company_id = CompanyRegister::where('corpid',$corpId)->value('company_id');

        return self::where('company_id',$company_id)->column('platform_departid');
    }

}
