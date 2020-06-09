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


    //根据云推送数据 更新部门信息
    public  function updateDepartmentInfo($_data,$corpId)
    {

        $company_id = CompanyRegister::where('corpid',$corpId)->value('company_id');
        if(!$company_id){
              return json_error(20050); 
        }
        $data = [];

        //"org_dept_create":表示部门创建;   "org_dept_modify"：表示部门修改;  "org_dept_remove": 表示企业部门删除;
        switch ($_data['syncAction']) {
            case 'org_dept_create':
            case 'org_dept_modify':

                $data['company_id'] = $company_id;
                $data['platform_departid'] = $_data['id'] ?? '';
                $data['dept_name'] = $_data['name'] ?? '';

                //获取parentid主键
                $_parentid = self::where('platform_departid',$_data['parentid'])->where('company_id',$company_id)->value('id');
                $data['parentid'] = $_parentid ?? 0;

                $data['sort'] = 255;
                $data['dept_hiding'] = $_data['deptHiding'] ? 1 : 0;
                $data['sourceIdentifier'] = '';
                $data['create_time'] = date('Y-m-d H:i:s',time());
                
                $isInsertOrUpdate = Db::name('company_department')
                                    ->where('platform_departid',$_data['id'])
                                    ->where('company_id',$company_id)
                                    ->find();

                if(!$isInsertOrUpdate){
                    //新增
                    Db::name('company_department')->insert($data);
                }else{
                    //修改
                    Db::name('company_department')->where('id',$isInsertOrUpdate['id'])->update($data);
                }
                
                
                break;

            case 'org_dept_remove':
                
                break;
            default:
                # code...
                break;
        }

        return true;
    }
    

}
