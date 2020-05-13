<?php
declare (strict_types=1);

namespace app\model;

use think\Model;
use app\traits\ModelTrait;
use think\facade\Db;

/**
 * 用户
 * Class User
 * @package app\model
 * @author  2066362155@qq.com
 */
class DTDepartment extends Model
{

    use ModelTrait;

    protected $table = "dc_company_department";
    public  static $_table  = "dc_company_department";


    //注册
    public function registerDepartment($_data,$company_id){
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
        return self::saveAll($data);
    }
}
