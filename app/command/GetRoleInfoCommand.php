<?php
namespace app\command;
use app\controller\api\Dingtalk;

class GetRoleInfoCommand extends BaseCommand
{
    /**
     * @var string 指令名称
     */
    protected $scriptName = "getRole";

    /**
     * 执行入口(处理业务逻辑)
     */
    protected function _execute()
    {   
        $DingTalk = new Dingtalk;
        return $DingTalk->getRoleList();
    }
}