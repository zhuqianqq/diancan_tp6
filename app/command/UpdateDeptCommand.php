<?php
namespace app\command;
use app\controller\api\CronJob;

class UpdateDeptCommand extends BaseCommand
{
    /**
     * @var string 指令名称
     */
    protected $scriptName = "update_dept";

    /**
     * 执行入口(处理业务逻辑)
     */
    protected function _execute()
    {
       CronJob::updateDept();
       //$this->output->writeln('send_message');
    }
}


