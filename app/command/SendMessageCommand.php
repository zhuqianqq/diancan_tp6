<?php
namespace app\command;
use app\controller\api\CronJob;

class SendMessageCommand extends BaseCommand
{
    /**
     * @var string 指令名称
     */
    protected $scriptName = "send_message";

    /**
     * 执行入口(处理业务逻辑)
     */
    protected function _execute()
    {
       CronJob::sendMessage();
       //$this->output->writeln('send_message');
    }
}


