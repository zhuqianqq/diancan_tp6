<?php
namespace app\command;

class TestCommand extends BaseCommand
{
    /**
     * @var string 指令名称
     */
    protected $scriptName = "test";

    /**
     * 执行入口(处理业务逻辑)
     */
    protected function _execute()
    {
        $this->output->writeln("This is test command");
    }
}