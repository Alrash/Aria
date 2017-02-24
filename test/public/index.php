<?php
/**
 * Author: Alrash
 * Date: 2017/01/16 22:04
 * Description: 前台入口文件
 */

//加载通用配置文件
require(dirname(dirname(__DIR__)) . '/common/config/setting.php');
//加载环境配置文件
require(dirname(__DIR__) . '/config/setting.php');
//加载环境设置类文件
require(APP_CORE_PATH . '/Aria.php');

use Aria\Aria;

//设置自动加载类环境
Aria::setClassMap([APP_COMMON_PATH . '/config', APP_ENV_PATH . '/config']);
Aria::register();
//初始化
Aria::init();

//运行
Aria::$app->run();
