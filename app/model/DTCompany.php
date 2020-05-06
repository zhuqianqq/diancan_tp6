<?php
declare (strict_types=1);

namespace app\model;

use think\Model;
use app\traits\ModelTrait;


class DTCompany extends Model
{
    use ModelTrait;
    
    protected $table = "dc_company_register";

    protected $pk = "company_id";
}

