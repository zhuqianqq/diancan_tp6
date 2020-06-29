<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
//命令行脚本注册
return [
    // 指令定义
    'commands' => [
        \app\command\TestCommand::class,
        \app\command\SendMessageCommand::class,
        \app\command\GetRoleInfoCommand::class,
    ],

];

