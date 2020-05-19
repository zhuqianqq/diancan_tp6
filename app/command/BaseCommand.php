<?php
/**
 * 命令行脚本抽象基类
 */
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

abstract class BaseCommand extends Command
{
    /**
     * @var string 脚本指令名称
     */
    protected $scriptName;

    /**
     * @var int 允许运行的最大进程数量
     */
    protected $maxProcessNum = 1;

    /**
     * 配置指令
     */
    protected function configure()
    {
        if(!empty($this->scriptName)){
            $this->setName($this->scriptName); //设置指令名称
        }
    }

    /**
     * 检测脚本运行进程数
     */
    protected function _init()
    {
        // if(strtoupper(substr(PHP_OS,0,3)) !== 'WIN'){
        //     $think_path = $this->app->getRootPath().'think'; //think脚本的路径
        //     $cmd = "ps -ef | grep '{$think_path}' | grep '{$this->scriptName}' | grep -v grep";
        //     @exec($cmd, $output, $retval);
        //     $num = count($output);

        //     if ($num > $this->maxProcessNum) {
        //         $this->log("process_num:{$num} > max_num:{$this->maxProcessNum}|exit");
        //         exit();
        //     }
        // }
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        //$this->_init();
        //$this->log('start');
        //$this->_execute();
        //$this->log('end|memory:' . (memory_get_usage(true) / 1024 / 1024) . 'MB|PeakMemory:' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB');
    }

    /**
     * 执行指令,子类实现
     * @return mixed
     */
    abstract protected function _execute();

    /**
     * 获取脚本进程数量
     * @param sring $script_name 脚本进程的命令名称
     * @param string $arg 参数
     * @return int
     */
    public function getProcessNum($script_name, $arg = '')
    {
        // if (empty($script_name)) {
        //     return 0;
        // }

        // $script_name = escapeshellcmd($script_name);
        // if ($arg !== '') {
        //     $sh = "ps -ef | grep -E '{$script_name} {$arg}$'"; //如果带参数，这里要用扩展表达式判断
        // } else {
        //     $sh = "ps -ef | grep '{$script_name}' | grep -v grep";
        // }
        // @exec($sh, $output, $retval);

        // return count($output);
    }

    /**
     * 记录日志
     * @param string|array $msg
     */
    protected function log($msg)
    {
        if(is_array($msg)){
            $msg = json_encode($msg,JSON_UNESCAPED_UNICODE);
        }
        Log::info("script".DIRECTORY_SEPARATOR.$this->scriptName.':'.$msg);
    }

    /**
     * 检测脚本停止文件
     * @return bool
     */
    protected function checkScriptStop()
    {
        $file = $this->app->getRuntimePath().$this->scriptName.".stop";
        return file_exists($file);
    }
}